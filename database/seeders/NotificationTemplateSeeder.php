<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['auth.otp', 'Login OTP', ['sms'], '{otp} is your Krishi Junction verification code. Valid for {minutes} minutes. Do not share it.', null, null, ['otp', 'minutes']],
            ['user.welcome', 'Welcome', ['sms', 'database'], 'Welcome to Krishi Junction, {name}! Buy, sell and finance tractors in one place.', 'Welcome to Krishi Junction', null, ['name']],
            ['listing.submitted', 'Listing submitted', ['sms', 'database'], 'Your listing {reference} is under review. We will confirm within {hours} hours.', 'Your listing is under review', null, ['reference', 'hours']],
            ['listing.approved', 'Listing approved', ['sms', 'database'], 'Good news! Your listing {reference} is now live at {url}', 'Your listing is live', null, ['reference', 'url']],
            ['listing.rejected', 'Listing rejected', ['sms', 'database'], 'Your listing {reference} needs changes: {reason}. Edit and resubmit.', 'Your listing needs changes', null, ['reference', 'reason']],
            ['listing.expiring', 'Listing expiring', ['sms', 'database'], 'Your listing {reference} expires in {days} days. Renew it to stay visible.', 'Your listing expires soon', null, ['reference', 'days']],
            ['listing.expired', 'Listing expired', ['sms', 'database'], 'Your listing {reference} has expired. Repost it any time from your account.', 'Your listing has expired', null, ['reference']],
            ['listing.sold', 'Listing marked sold', ['database'], null, 'Listing marked as sold', null, ['reference']],
            ['lead.created.buyer', 'Enquiry received (buyer)', ['sms'], 'Thanks {name}! Your enquiry {reference} is registered. A dealer will call you shortly.', null, null, ['name', 'reference']],
            ['lead.assigned.dealer', 'New lead (dealer)', ['sms', 'database'], 'New Krishi Junction lead {reference} in {district}. Respond within {sla} minutes.', 'New lead assigned', null, ['reference', 'district', 'sla']],
            ['lead.assigned.seller', 'New buyer (seller)', ['sms', 'database'], 'A buyer is interested in your listing {reference}. See details in your account.', 'You have a new buyer', null, ['reference']],
            ['lead.escalated', 'Lead escalated', ['database'], null, 'Lead escalated after SLA breach', null, ['reference']],
            ['lead.followup', 'Follow-up due', ['database'], null, 'Follow-up due today', null, ['reference']],
            ['dealer.registered', 'Dealer registration received', ['sms', 'database'], 'Thanks for registering {business}. We will verify your documents within 2 working days.', 'Registration received', null, ['business']],
            ['dealer.verified', 'Dealer verified', ['sms', 'database'], 'Your dealer account {code} is verified. Log in to receive buyer leads.', 'Your dealer account is verified', null, ['code']],
            ['dealer.rejected', 'Dealer rejected', ['sms', 'database'], 'Your dealer registration needs attention: {reason}', 'Registration needs attention', null, ['reason']],
            ['loan.submitted', 'Loan submitted', ['sms', 'database'], 'Loan application {reference} received. Track its status in your account.', 'Loan application received', null, ['reference']],
            ['loan.docs_pending', 'Loan documents pending', ['sms', 'database'], 'Application {reference}: we still need {documents}. Upload to continue.', 'Documents pending', null, ['reference', 'documents']],
            ['loan.sanctioned', 'Loan sanctioned', ['sms', 'database'], 'Congratulations! Loan {reference} is sanctioned for Rs {amount}.', 'Your loan is sanctioned', null, ['reference', 'amount']],
            ['loan.rejected', 'Loan rejected', ['sms', 'database'], 'Loan {reference} could not be approved. Our team will call you with options.', 'Loan application update', null, ['reference']],
            ['loan.disbursed', 'Loan disbursed', ['sms', 'database'], 'Loan {reference} has been disbursed. Thank you for choosing Krishi Junction.', 'Loan disbursed', null, ['reference']],
            ['inspection.scheduled', 'Inspection scheduled', ['sms', 'database'], 'Inspection for {reference} is scheduled on {date}. Our inspector will call you.', 'Inspection scheduled', null, ['reference', 'date']],
            ['inspection.completed', 'Inspection completed', ['sms', 'database'], 'Inspection complete for {reference}. Grade {grade}. Report available in your account.', 'Inspection report ready', null, ['reference', 'grade']],
            ['review.approved', 'Review published', ['database'], null, 'Your review is published', null, ['title']],
            ['review.rejected', 'Review rejected', ['database'], null, 'Your review was not published', null, ['reason']],
        ];

        foreach ($templates as [$key, $name, $channels, $sms, $subject, $body, $vars]) {
            NotificationTemplate::updateOrCreate(
                ['event_key' => $key],
                [
                    'name' => $name,
                    'channels' => $channels,
                    'sms_body' => $sms,
                    'email_subject' => $subject,
                    'email_body' => $body,
                    'variables' => $vars,
                    'is_active' => true,
                ],
            );
        }

        $this->command?->info('Notification templates: '.count($templates));
    }
}
