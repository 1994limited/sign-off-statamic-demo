<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use NineteenNinetyFour\SignOff\SignOff;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

/**
 * The demo's people, and a week of work in progress: changes waiting at
 * each step, one sent back, one signed off, and an urgent fix published
 * without sign-off. Everyone's password is "password", so this only runs
 * locally.
 */
class SetUpDemo extends Command
{
    protected $signature = 'demo:setup';

    protected $description = 'Make the demo accounts, and put some content through sign-off';

    protected SignOff $signOff;

    /** @var array<int, array{0: \Carbon\Carbon, 1: string}> */
    protected array $steps = [];

    public function handle(SignOff $signOff): int
    {
        if (! app()->environment('local')) {
            $this->error('demo:setup only runs with APP_ENV=local: its passwords are public.');

            return self::FAILURE;
        }

        $this->signOff = $signOff;

        $this->people();

        if ($signOff->submissions()->all()->isNotEmpty()) {
            $this->warn('The content has already been through sign-off. To start again, see "Starting again" in the README.');

            return self::SUCCESS;
        }

        try {
            $this->work();
        } finally {
            Carbon::setTestNow();
        }

        foreach (collect($this->steps)->sortBy(fn ($step) => $step[0]->timestamp) as [$when, $what]) {
            $this->line('  <fg=gray>'.$when->format('D H:i').'</>  '.$what);
        }

        $this->newLine();
        $this->info('Done. Sign in at /cp as anyone above to see their part.');

        return self::SUCCESS;
    }

    protected function people(): void
    {
        $people = [
            ['alex@example.com', 'Alex Morgan', 'Writes the website', ['author'], []],
            ['priya@example.com', 'Priya Shah', 'Pricing team', [], ['pricing']],
            ['lee@example.com', 'Lee Okafor', 'Legal', ['legal'], []],
            ['pat@example.com', 'Pat Kowalski', 'Publisher', ['publisher'], []],
            ['admin@example.com', 'Admin', 'Super user', [], []],
        ];

        $rows = [];

        foreach ($people as [$email, $name, $job, $roles, $groups]) {
            $user = User::findByEmail($email) ?? User::make()->email($email);
            $user->set('name', $name)->password('password')->explicitRoles($roles)->groups($groups)->makeSuper($email === 'admin@example.com')->save();
            $rows[] = [$email, $name, $job];
        }

        $this->table(['Email (password: password)', 'Name', 'Role'], $rows);
    }

    /**
     * Oldest first, each at the time it happened.
     */
    protected function work(): void
    {
        [$alex, $priya, $lee, $pat, $admin] = array_map(fn ($email) => User::findByEmail($email), [
            'alex@example.com', 'priya@example.com', 'lee@example.com', 'pat@example.com', 'admin@example.com',
        ]);

        // A news story, signed off last week.
        $this->at('-6 days 10:15');
        $story = Entry::make()->collection('news')->slug('breakwater-share-offer')->published(false)->date(now()->addDay()->startOfDay())->data([
            'title' => 'Breakwater share offer opens to new members',
            'intro' => 'Shares from £50 will pay for a battery at Breakwater Farm, storing the turbines’ power for the evening peak.',
            'content' => [
                $this->paragraph('The offer is open to anyone living in Essex or Suffolk until the end of January. Shares are expected to earn 3% interest a year from 2028.'),
                $this->paragraph('Full details, and the risks of investing, are in the share offer document.'),
            ],
        ]);
        $story->save();
        $this->signOff->submit($alex, $story, 'For Monday morning, please');
        $this->step("Alex submits a news story");

        $this->at('-5 days 09:02');
        $this->signOff->approve($pat, $story, 'Lovely. Published.');
        $this->step('Pat approves it, and it’s published');

        // The switching page, sent back by Legal.
        $this->at('-4 days 14:40');
        $switching = $this->change('pages', 'switching', $alex, [
            'intro' => 'Moving your home to Saltmarsh Energy is free, quick, and your supply won’t be interrupted.',
        ]);
        $this->signOff->submit($alex, $switching, 'Small tidy of the intro');
        $this->step('Alex submits a change to "Switching to us"');

        $this->at('-3 days 11:20');
        $this->signOff->reject($lee, $switching, 'The 21 days isn’t ours to guarantee: it depends on the old supplier. Please say “usually within 21 days” instead.');
        $this->step('Lee sends it back');

        $this->at('-2 days 16:05');
        $this->change('pages', 'switching', $alex, [
            'content' => $this->replaceText(Entry::query()->where('collection', 'pages')->where('slug', 'switching')->first()->fromWorkingCopy()->get('content'), 'Your switch is guaranteed to complete within 21 days.', 'Your switch usually completes within 21 days.'),
        ]);
        $this->step('Alex makes the change, but hasn’t submitted it again yet');

        // A price cut on Fixed Green 12, waiting for Legal.
        $this->at('-3 days 09:30');
        $fixed = $this->change('tariffs', 'fixed-green-12', $alex, [
            'unit_rate' => '24.50',
            'standing_charge' => '47.90',
            'green_claim' => '100% renewable electricity: 74% from our own turbines and roofs, the rest bought from other community generators in the East of England.',
        ]);
        $this->signOff->submit($alex, $fixed, 'New prices from 1 November, from the pricing model');
        $this->step('Alex submits a price cut on Fixed Green 12');

        $this->at('-2 days 15:12');
        $this->signOff->approve($priya, $fixed, 'Matches the model. Standing charge checked against Ofgem’s cap.');
        $this->step('Priya approves the prices; it waits for Lee');

        // The community fund deadline, fixed in a hurry.
        $this->at('-2 days 08:47');
        $fund = Entry::query()->where('collection', 'pages')->where('slug', 'community-fund')->first();
        $fixedFund = $fund->fromWorkingCopy()->set('content', $this->replaceText($fund->get('content'), 'Friday 28 November', 'Friday 5 December'));
        $fixedFund->publish(['user' => $admin, 'message' => 'Urgent: the deadline was wrong in this morning’s newsletter']);
        $this->step('The admin fixes a deadline without sign-off');

        // A new tariff, waiting for the Publisher.
        $this->at('-2 days 10:00');
        $night = Entry::make()->collection('tariffs')->slug('night-saver')->published(false)->data([
            'title' => 'Night Saver',
            'summary' => 'Electricity at less than half price from midnight to 7am, for homes with an electric car or storage heaters. 29.80p per kWh by day.',
            'contract' => 'fixed',
            'length' => '12 months',
            'exit_fee' => '£30',
            'unit_rate' => '13.40',
            'standing_charge' => '52.30',
            'green_claim' => '100% renewable electricity, with night-time wind from our Breakwater turbines.',
        ]);
        $night->save();
        Collection::find('tariffs')->structure()->in('default')->append($night)->save();
        $this->signOff->submit($alex, $night, 'Our first time-of-use tariff');
        $this->step('Alex submits a new tariff, Night Saver');

        $this->at('-1 day 11:30');
        $this->signOff->approve($priya, $night, 'Night rate agreed with the board.');
        $this->at('-1 day 15:45');
        $this->signOff->approve($lee, $night, 'Fine. The “cheaper” claim is fair: the night rate is less than half the day rate.');
        $this->step('Priya and Lee approve it; it waits for Pat');

        // A new page, waiting for Legal.
        $this->at('-1 day 09:10');
        $energy = Entry::make()->collection('pages')->slug('our-energy')->published(false)->data([
            'title' => 'Where our energy comes from',
            'intro' => 'Two turbines, eleven roofs, and a lot of Essex wind.',
            'content' => [
                $this->heading('Breakwater Farm'),
                $this->paragraph('Our two turbines have stood at Breakwater Farm since 2016. Between them they make enough electricity for 2,600 homes a year.'),
                $this->heading('Rooftop solar'),
                $this->paragraph('Panels on eleven schools, halls and workshops make another 900 homes’ worth, and the buildings get cheaper electricity in return.'),
                $this->paragraph('We are the greenest supplier in Essex.'),
            ],
        ]);
        $energy->save();
        $this->signOff->submit($alex, $energy, 'New page for the menu, as discussed');
        $this->step('Alex submits a new page about the turbines');

        // A price change on Flexible Tide, waiting for Pricing.
        $this->at('-3 hours');
        $tide = $this->change('tariffs', 'flexible-tide', $alex, [
            'unit_rate' => '23.95',
            'summary' => 'No contract and no exit fee. The price follows the market, and we tell you 30 days before it changes.',
        ]);
        $this->signOff->submit($alex, $tide, 'November price change');
        $this->step('Alex submits a price change on Flexible Tide');
    }

    /**
     * A change to a live entry, saved to its working copy as the Control
     * Panel saves it.
     */
    protected function change(string $collection, string $slug, UserContract $by, array $data): EntryContract
    {
        $entry = Entry::query()->where('collection', $collection)->where('slug', $slug)->first();

        $entry->fromWorkingCopy()->merge($data)->makeWorkingCopy()->user($by)->save();

        return Entry::find($entry->id());
    }

    protected function at(string $when): void
    {
        Carbon::setTestNow();
        Carbon::setTestNow(Carbon::parse($when));
    }

    protected function step(string $what): void
    {
        $this->steps[] = [now()->copy(), $what];
    }

    protected function paragraph(string $text): array
    {
        return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
    }

    protected function heading(string $text): array
    {
        return ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $text]]];
    }

    protected function replaceText(array $nodes, string $from, string $to): array
    {
        array_walk_recursive($nodes, function (&$value, $key) use ($from, $to) {
            if (is_string($value)) {
                $value = str_replace($from, $to, $value);
            }
        });

        return $nodes;
    }
}
