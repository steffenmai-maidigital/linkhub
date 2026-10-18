<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Branding: Firmenname (ersetzt "LinkHub" im Header), Logo, Farben, Footer
            // Branding: company name (replaces "LinkHub" in the header), logo, colours, footer
            'brand_company_name'  => '',
            'brand_logo_path'     => '',
            'brand_header_color'  => '',
            'brand_accent_color'  => '',
            'brand_footer_color'  => '',
            'brand_footer_text'   => '',
            // Active Directory: Gruppen -> Rollen
            // Active Directory: groups -> roles
            'ad_group_admins'     => config('linkhub.ad_groups.admins'),
            'ad_group_managers'   => config('linkhub.ad_groups.managers'),
            // Lizenz / Licence
            'license_company'     => '',
            'license_key'         => '',
            // Backup
            'backup_retention_days' => (string) config('linkhub.backup_min_retention_days'),
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
