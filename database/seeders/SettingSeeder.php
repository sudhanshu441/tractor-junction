<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // group, key, value, type, label
            ['general', 'site_name', 'Krishi Junction', 'string', 'Site name'],
            ['general', 'tagline', "India's rural machinery marketplace", 'string', 'Tagline'],
            ['general', 'support_mobile', '', 'string', 'Support mobile'],
            ['general', 'support_email', 'support@krishijunction.com', 'string', 'Support email'],
            ['general', 'address', '', 'text', 'Registered address'],
            ['general', 'logo', 'brand/logo-horizontal.svg', 'file', 'Logo'],
            ['general', 'favicon', 'brand/favicon.svg', 'file', 'Favicon'],
            ['general', 'maintenance_mode', '0', 'bool', 'Maintenance mode'],

            ['seo', 'meta_title', 'New & Used Tractors in India — Price, Specs | Krishi Junction', 'string', 'Default meta title'],
            ['seo', 'meta_description', 'Compare new and used tractors, implements and harvesters. Check on-road prices, EMI, dealers and reviews across India.', 'text', 'Default meta description'],
            ['seo', 'google_analytics_id', '', 'string', 'GA4 measurement ID'],
            ['seo', 'gtm_id', '', 'string', 'Google Tag Manager ID'],

            ['social', 'facebook', '', 'string', 'Facebook URL'],
            ['social', 'youtube', '', 'string', 'YouTube URL'],
            ['social', 'instagram', '', 'string', 'Instagram URL'],
            ['social', 'whatsapp', '', 'string', 'WhatsApp number'],

            ['business', 'listing_expiry_days', '60', 'int', 'Used listing expiry (days)'],
            ['business', 'listing_min_photos', '4', 'int', 'Minimum listing photos'],
            ['business', 'listing_max_photos', '12', 'int', 'Maximum listing photos'],
            ['business', 'moderation_sla_hours', '6', 'int', 'Moderation SLA (hours)'],
            ['business', 'lead_response_sla_minutes', '120', 'int', 'Dealer lead response SLA (minutes)'],
            ['business', 'lead_duplicate_window_days', '7', 'int', 'Duplicate lead window (days)'],
            ['business', 'default_interest_rate', '11.5', 'string', 'Default EMI interest rate (%)'],
            ['business', 'default_down_payment_percent', '20', 'int', 'Default down payment (%)'],

            ['otp', 'ttl_minutes', '10', 'int', 'OTP validity (minutes)'],
            ['otp', 'max_attempts', '5', 'int', 'Max OTP attempts'],
            ['otp', 'rate_per_hour', '5', 'int', 'OTP requests per mobile per hour'],
        ];

        foreach ($settings as [$group, $key, $value, $type, $label]) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['group' => $group, 'value' => $value, 'type' => $type, 'label' => $label],
            );
        }

        $this->command?->info('Settings: '.count($settings).' keys');
    }
}
