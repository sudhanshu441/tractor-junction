<?php

namespace Database\Seeders;

use App\Domain\Content\Services\ContentService;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Faq;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Tag;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The content the site cannot launch without: legal pages, the FAQ set, the
 * header and footer menus, and enough editorial to prove the section works.
 *
 * The legal pages carry placeholder wording marked for the client's lawyer —
 * shipping invented terms and conditions would be worse than shipping none.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $content = app(ContentService::class);

        $this->seedPages();
        $this->seedFaqs();
        $this->seedMenus();
        $this->seedTestimonials();
        $this->seedPosts($content);

        ContentService::flush();

        $this->command?->info('  Content: '.Page::count().' pages · '.Faq::count().' FAQs · '
            .Blog::count().' posts · '.Menu::count().' menus');
    }

    private function seedPages(): void
    {
        $pages = [
            ['About Krishi Junction', 'about-us', 0, true, <<<'HTML'
                <p>Krishi Junction is a marketplace for tractors, implements and farm machinery in India.
                We list new models with honest on-road prices, carry verified used machines from farmers
                and dealers, and connect buyers to the dealer, lender or insurer nearest them.</p>

                <h2>What we do</h2>
                <ul>
                  <li>Publish specifications and prices for every major brand, state by state.</li>
                  <li>Let a farmer list a used machine in a few minutes from a phone.</li>
                  <li>Inspect used machines on request and publish a graded report.</li>
                  <li>Route enquiries to dealers who actually operate in that district.</li>
                </ul>

                <h2>What we do not do</h2>
                <p>We are not a dealer and we do not take a cut of a machine sale. We do not sell your
                phone number: an enquiry goes to the dealer you asked about, and nobody else.</p>
                HTML],

            ['Privacy policy', 'privacy-policy', 1, true, <<<'HTML'
                <p><strong>This is placeholder wording and must be reviewed by a lawyer before launch.</strong>
                It describes what the software actually does, which is the right starting point for that review.</p>

                <h2>What we collect</h2>
                <ul>
                  <li>Your name and mobile number when you make an enquiry or list a machine.</li>
                  <li>Your district, so an enquiry reaches a dealer who can actually reach you.</li>
                  <li>KYC and income documents, only if you apply for a loan through us.</li>
                  <li>Pages you view, to understand what the site is used for.</li>
                </ul>

                <h2>Who sees it</h2>
                <p>An enquiry is shared with the dealer or seller you enquired about. A loan application is
                shared only with lenders you agree to send it to. Nothing is sold to anyone.</p>

                <h2>KYC documents</h2>
                <p>Identity and income documents are stored on a private server, never on the public website,
                and are opened through links that expire in minutes. We store PAN and Aadhaar numbers masked —
                only the last four characters are kept.</p>

                <h2>Your choices</h2>
                <p>You can ask us to delete your account and listings at any time by writing to the support
                address on our contact page.</p>
                HTML],

            ['Terms & conditions', 'terms-and-conditions', 2, true, <<<'HTML'
                <p><strong>This is placeholder wording and must be reviewed by a lawyer before launch.</strong></p>

                <h2>What this website is</h2>
                <p>Krishi Junction is a listing and introduction service. We are not party to any sale,
                loan or insurance contract you enter into with a dealer, seller, lender or insurer.</p>

                <h2>Prices</h2>
                <p>Prices shown are indicative ex-showroom or on-road estimates collected from public sources
                and dealers. The final price is whatever the dealer quotes you in writing.</p>

                <h2>Used listings</h2>
                <p>A used machine is described by its seller. We check listings before publishing them and
                we inspect machines on request, but an inspection grade is an opinion formed on one day and
                is not a warranty.</p>

                <h2>Your listing</h2>
                <p>You must own or be authorised to sell what you list. We remove listings that are
                duplicated, misdescribed, or advertise a machine that is already sold.</p>
                HTML],
        ];

        foreach ($pages as [$title, $slug, $order, $footer, $html]) {
            Page::updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'content' => trim(preg_replace('/^\s+/m', '', $html)),
                'template' => 'default',
                'is_active' => true,
                'show_in_footer' => $footer,
                'sort_order' => $order,
            ]);
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            ['general', 'Is Krishi Junction free to use?', 'Yes. Browsing, comparing, listing a used machine and making an enquiry are all free for farmers. We earn from dealer subscriptions and paid promotions, which is why a dealer never pays us for a lead they did not ask for.'],
            ['general', 'Do you sell tractors yourselves?', 'No. We are a marketplace. Every machine is sold by a dealer or by another farmer; we introduce you to them and stay out of the price negotiation.'],
            ['general', 'Which languages do you support?', 'The whole website is available in Hindi and English. Use the language button at the top of any page.'],

            ['buying', 'Is the price shown the final price?', 'No. Prices are indicative ex-showroom or on-road estimates. The final on-road figure depends on your state, RTO charges, insurance and any dealer offer, so always get it in writing from the dealer.'],
            ['buying', 'How do I check a used tractor before buying?', 'Ask for the RC and insurance papers, check engine hours against the seller claim, and look for oil leaks around the hydraulics. You can also request a Krishi Junction inspection — an inspector scores the machine on a 25-point checklist and publishes a grade.'],
            ['buying', 'What does the verified badge mean?', 'It means one of our inspectors physically examined that machine and their report was approved by our team. It is not a warranty, but it is a real person who saw the tractor.'],

            ['selling', 'How long does a listing stay live?', 'Sixty days. We remind you before it expires and you can renew it in one tap.'],
            ['selling', 'Why was my listing rejected?', 'Usually photos that do not show the machine clearly, a phone number written in the description, or a price far outside the market band. The reason is always sent to you in full — fix it and resubmit.'],
            ['selling', 'Will my phone number be public?', 'No. Buyers see a masked number and must verify their own mobile before yours is revealed, which keeps spam callers away.'],

            ['loan', 'What documents do I need for a tractor loan?', 'Aadhaar, PAN, a land record and six months of bank statements. Lenders may ask for more depending on the amount.'],
            ['loan', 'Can I repay after the harvest instead of monthly?', 'Yes, with several lenders. Our EMI calculator supports quarterly, half-yearly and yearly repayment because farm income does not arrive monthly.'],
            ['loan', 'Are my documents safe?', 'They are stored on a private server, never on the public website, and are opened only through links that expire in minutes. We store your PAN and Aadhaar masked.'],

            ['dealer', 'How do I list my dealership?', 'Use the "Become a dealer" form. We verify your GST and dealership papers before your profile goes live, which usually takes a working day.'],
            ['dealer', 'How many leads will I get?', 'That depends on your plan and your district. Leads are routed to verified dealers who cover the buyer district, ranked by plan and by how quickly you answer.'],
        ];

        foreach ($faqs as $i => [$category, $question, $answer]) {
            Faq::updateOrCreate(['question' => $question], [
                'category' => $category,
                'answer' => $answer,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }

    private function seedMenus(): void
    {
        $menus = [
            'header' => ['Header', [
                ['New Tractors', '/tractors'],
                ['Used', '/used'],
                ['Implements', '/implements'],
                ['Compare', '/compare'],
                ['Dealers', '/dealers'],
                ['Loan & EMI', '/loan'],
                ['Insurance', '/tractor-insurance'],
                ['News', '/news'],
            ]],
            'footer_1' => ['Footer — Company', [
                ['About us', '/about-us'],
                ['Contact us', '/contact'],
                ['Become a dealer', '/dealers/become-a-dealer'],
                ['Sell your tractor', '/sell'],
            ]],
            'footer_2' => ['Footer — Explore', [
                ['New tractors', '/tractors'],
                ['Used tractors', '/used'],
                ['Offers', '/offers'],
                ['Videos', '/videos'],
                ['FAQ', '/faq'],
            ]],
        ];

        foreach ($menus as $slug => [$name, $items]) {
            $menu = Menu::updateOrCreate(['slug' => $slug], ['name' => $name]);

            foreach ($items as $i => [$label, $url]) {
                $menu->items()->updateOrCreate(
                    ['label' => $label, 'parent_id' => null],
                    ['url' => $url, 'sort_order' => $i, 'is_active' => true],
                );
            }
        }
    }

    private function seedTestimonials(): void
    {
        foreach ([
            ['Ramesh Kumar', 'Farmer', 'Sitapur', 'Sold my old 475 in nine days. The buyer called me directly, no middleman asking for a cut.', 5],
            ['Harpreet Singh', 'Farmer', 'Ludhiana', 'Compared four models side by side and found the HP I actually needed was lower than the dealer was pushing.', 5],
            ['Sunita Devi', 'Farmer', 'Hardoi', 'The EMI calculator with half-yearly repayment matched how my money actually comes in. That is what convinced me.', 4],
        ] as $i => [$name, $designation, $city, $message, $rating]) {
            Testimonial::updateOrCreate(['name' => $name], [
                'designation' => $designation,
                'city' => $city,
                'message' => $message,
                'rating' => $rating,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }

    private function seedPosts(ContentService $content): void
    {
        $categories = [
            ['Buying guides', 'buying-guides'],
            ['Government schemes', 'government-schemes'],
            ['New launches', 'new-launches'],
        ];

        foreach ($categories as $i => [$name, $slug]) {
            BlogCategory::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'sort_order' => $i, 'is_active' => true,
            ]);
        }

        $author = User::whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->first();

        $posts = [
            ['How much horsepower do you actually need?', 'buying-guides', 'guide', ['hp', 'buying'], true, <<<'HTML'
                <p>Dealers sell horsepower because horsepower sells. But an oversized tractor burns more
                diesel every hour of every year, and the extra power sits unused for most of the work
                you actually do.</p>

                <h2>Start from the implement, not the tractor</h2>
                <p>The heaviest implement you will pull decides the HP you need. A nine-tyne cultivator
                needs roughly 35–40 HP in medium soil. A rotavator in wet, puddled soil needs closer to
                50. A trolley on a road needs far less than either.</p>

                <h2>Then check your soil</h2>
                <p>Black cotton soil holds the implement and demands more pull than sandy loam. If your
                land is heavy, add roughly 15% to the figure above.</p>

                <h2>Then check the hours</h2>
                <p>If the tractor works fewer than 500 hours a year, the diesel penalty of a bigger engine
                is small and resale value may justify it. Above 800 hours, every extra litre an hour is
                real money.</p>

                <h2>A rule that survives contact with the field</h2>
                <p>Buy for the implement you use most weeks, not the one you use twice a year. Hire the
                bigger machine for those two weeks.</p>
                HTML],

            ['Tractor subsidy schemes: how to actually apply', 'government-schemes', 'guide', ['subsidy', 'schemes'], false, <<<'HTML'
                <p>Most state agriculture departments run a subsidy on farm machinery, and most farmers
                who qualify never claim it — not because the scheme is hidden, but because the sequence
                is easy to get wrong.</p>

                <h2>Apply before you buy</h2>
                <p>This is the step that costs people the money. Nearly every scheme requires approval
                <em>before</em> the purchase. A bill dated before your application is usually rejected
                outright.</p>

                <h2>What you will need</h2>
                <ul>
                  <li>Aadhaar and a bank passbook in the same name.</li>
                  <li>Land records showing the holding in your name.</li>
                  <li>A caste certificate, where the scheme reserves a share.</li>
                  <li>A quotation from an empanelled dealer — not any dealer.</li>
                </ul>

                <h2>After approval</h2>
                <p>Buy only from the empanelled dealer named in the approval, keep the original invoice,
                and submit it within the window stated on your permit. The subsidy is credited to the
                bank account linked to your Aadhaar.</p>

                <p>Check your own state portal for the current rate — it changes each financial year.</p>
                HTML],

            ['What engine hours really tell you about a used tractor', 'buying-guides', 'blog', ['used', 'inspection'], false, <<<'HTML'
                <p>Engine hours are the odometer of a tractor, and like an odometer they can be wound
                back. Here is how to read them against everything else on the machine.</p>

                <h2>The arithmetic</h2>
                <p>A working tractor on a mid-sized holding does 400–600 hours a year. A five-year-old
                machine showing 900 hours has either been lightly used or had its meter replaced. Ask
                which — a straight answer is a good sign.</p>

                <h2>What should match</h2>
                <ul>
                  <li>Tyre wear: original tyres rarely survive past 2,500 hours.</li>
                  <li>Seat and steering wear: 3,000 hours shows on both.</li>
                  <li>Pedal rubber: replaced pedals on a "low hours" machine is a question worth asking.</li>
                </ul>

                <h2>What hours do not tell you</h2>
                <p>Whether the oil was changed. A 4,000-hour tractor with a service record beats a
                1,500-hour tractor without one, every time.</p>
                HTML],
        ];

        foreach ($posts as $i => [$title, $categorySlug, $type, $tags, $featured, $html]) {
            $body = trim(preg_replace('/^\s+/m', '', $html));

            $post = Blog::updateOrCreate(['slug' => Str::slug($title)], [
                'blog_category_id' => BlogCategory::where('slug', $categorySlug)->value('id'),
                'author_id' => $author?->id,
                'type' => $type,
                'title' => $title,
                'content' => $body,
                'excerpt' => $content->excerptFrom($body),
                'reading_minutes' => $content->readingMinutes($body),
                'is_featured' => $featured,
                'status' => 'published',
                'published_at' => now()->subDays(($i + 1) * 3),
            ]);

            $post->tags()->sync(collect($tags)->map(fn ($tag) => Tag::firstOrCreate(
                ['slug' => Str::slug($tag)], ['name' => $tag],
            )->id));
        }
    }
}
