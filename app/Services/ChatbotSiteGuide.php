<?php

namespace App\Services;

use App\Models\News;
use App\Models\TrainingRequest;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * What GoBot knows about the website: its menus, every public page and the steps for common
 * tasks, written the way a visitor sees them (menu and button names). Prices, class sizes and
 * fees are read from the code, so the guide stays right when they change.
 */
class ChatbotSiteGuide
{
    public function text(): string
    {
        $site = rtrim((string) config('app.url'), '/');
        $plans = collect(TrainingRequest::OFFERED)->map(fn ($tier) => TrainingRequest::TIERS[$tier])->implode(' and ');
        $classMin = (int) config('volunteer.class_min');
        $classMax = (int) config('volunteer.class_max');
        $chapterFee = number_format((int) config('replication.class_fee'));
        $chapterSize = (int) config('replication.class_size');
        $extraTrainee = number_format((int) config('replication.additional_trainee_fee'));
        $chapterMax = (int) config('replication.max_trainees');

        return <<<GUIDE
WEBSITE GUIDE (the OneGoBike website, {$site})
Page addresses below start with {$site}.

MENUS
- Top menu (on phones: the ☰ menu button at the top right):
  • Who We Are → About Us (/about), Org Structure & History (/org-structure)
  • What We Do (/what-we-do)
  • Join The Movement → News & Updates (/news), Gallery Showcase (/gallery)
  • Reach Us (/contact)
  • Donate button (/donate)
- Footer (bottom of every page): Navigate (About Us, What We Do, Community, Contact) and Get Involved (Get Training, Donate, News & Updates, Gallery), plus Privacy Policy, Terms and Cookie Policy.
- Home page (/): the first screen has two buttons, "Volunteer with us" or "Get Trained". Further down: the programs slider, quick-access cards, impact numbers, latest news, and at the very end "How to Replicate Go Bike Project in Your Community".

PAGES
- About Us (/about): Go Bike's story, mission and people. "Be part of the story" at the bottom has a Get Training button.
- Org Structure & History (/org-structure): how Go Bike is organized and its history timeline.
- What We Do (/what-we-do): the 6 programs as cards (tap "View Details" on a card), then the individual training plans ("Learn To Save Lives"), then "Support Our Work" (Make a Donation).
  Programs and their pages:
  • Go Bike (/what-we-do/programs/gobike): youth volunteers trained as community responders.
  • Alwar (/what-we-do/programs/alwar): student responders in schools (disaster preparedness, first aid).
  • Ronda Kalusugan (/what-we-do/programs/ronda-kalusugan): health outreach, e.g. blood pressure and blood sugar checks.
  • PadyaKaalaman (/what-we-do/programs/padyakaalaman): education and learning programs.
  • Padyakaisipan (/what-we-do/programs/padyakaisipan): mental health awareness for young people.
  • Hatake Farm (/what-we-do/programs/hatake): community farming and food security.
- Volunteer (/volunteer): become a Go Biker. Sections: What You'll Do, What You Get, Requirements, How It Works, the sign-up form, and Volunteer FAQ.
- Replicate Go Bike (/replicate-go-bike): bring Go Bike to your own community (start a chapter); the training, fees and an inquiry form. It is not in the top menu: open it from the last section of the home page ("How to Replicate Go Bike Project in Your Community", button "Access the Program Now").
- Donate (/donate): give once or every month.
- News & Updates (/news): news articles; tap one to read it. Our Story (/our-story) tells how Go Bike started.
- Gallery Showcase (/gallery): photos and videos.
- Reach Us (/contact): contact form, email, phone, map and Facebook.
- Privacy Policy (/privacy-policy), Terms (/terms), Cookie Policy (/cookie-policy).

HOW TO
Volunteer (free):
1. Open Volunteer (/volunteer): tap "Volunteer with us" on the home page.
2. Scroll to "Ready to ride with us?" and fill in the form: About You, Where You Live, Your Volunteering (availability), Emergency Contact; if under 18, the Parent / Guardian part.
3. Tick the agreement and tap "Submit Application". The page then shows your place in line.
- Who can join: ages 13 to 25; ages 13–17 need parent or guardian consent; no experience needed.
- Training is free (2-day DRRM-CCAM with First Aid & Basic Life Support) and is given to a whole class: it starts once {$classMin} to {$classMax} volunteers have signed up. The "Next volunteer class" box on the page shows how full the next class is. Go Bike emails (and may call or text) the date and venue when the class is set.
- Want to train sooner? Book individual training (below).

Get individual first aid training (paid, starts on the date you choose, one-on-one or for your household):
1. Open What We Do (/what-we-do), or tap "Get Trained" on the home page or "Get Training" in the footer (/what-we-do#training-plan).
2. In "Learn To Save Lives", choose a plan: {$plans} per person. Standard is a 1-day First Aid & CPR training; Advance is the 2-day full First Aid & Basic Life Support module with disaster readiness.
3. The form "Schedule Your Training Session" opens: Full Name, Phone Number (required), Email Address, Home Address, Preferred Date (tomorrow up to 60 days ahead), Time of Day (optional), Message.
4. Tap "Submit Request". A thank-you note confirms it; the team calls within 24–48 hours to confirm. Training happens at your home, school, office or barangay hall in Pangasinan.

Donate:
1. Open Donate (/donate) with the Donate button at the top.
2. Choose once or monthly and an amount (USD presets or your own); choose Individual or Organization.
3. Fill in Your Details, then Payment Details: PayPal, GCash or Online Banking. Monthly donations use PayPal.
4. Finish on the payment page; a thank-you page confirms the donation. For questions about a donation, use Reach Us.

Start a Go Bike chapter in your community:
1. Open the home page, scroll to the last section "How to Replicate Go Bike Project in Your Community" and tap "Access the Program Now" (or open /replicate-go-bike).
2. Read the setup, training and operations sections.
3. Fill in the inquiry form (organization, type, contact person, municipality and province, number of trainees, preferred training date) and send it. The training fee can be paid early online (GCash or online banking) or at the training.
- Go Biker's Training fee: ₱{$chapterFee} for a class of up to {$chapterSize} trainees, plus ₱{$extraTrainee} for each additional trainee (up to {$chapterMax} trainees).

Contact Go Bike:
1. Open Reach Us (/contact).
2. Use the "Write To Us" form (Full Name, Email Address, Subject / Topic, Your Message), or call 0976 429 0145, email gobike@pydn.org, or message facebook.com/gobikeprojectofficial. Go Bike is in Bugallon, Pangasinan.

News: open Join The Movement → News & Updates (/news).
Photos: open Join The Movement → Gallery Showcase (/gallery).

RONDAGO APP
Go Bike's mobile app for residents (SOS button, home check-up visits, check-up history) and GoBiker volunteers (rondas, patient check-ups). For questions about the app, use Reach Us.

EMERGENCIES
For an emergency, call 911 or the local MDRRMO first. Go Bike is a supplementary responder organization.
{$this->latestNews($site)}
GUIDE;
    }

    /** The newest published articles, so "what's new?" can be answered. */
    private function latestNews(string $site): string
    {
        try {
            $items = Cache::remember('chatbot.latest_news', now()->addMinutes(10), fn () => News::published()
                ->latest('published_at')
                ->take(5)
                ->get(['title', 'slug', 'published_at'])
                ->map(fn (News $news) => "- {$news->title} ({$news->published_at?->format('M j, Y')}): /news/{$news->slug}")
                ->all());
        } catch (Throwable $e) {
            report($e);

            return '';
        }

        return $items ? "\nLATEST NEWS\n".implode("\n", $items)."\n" : '';
    }
}
