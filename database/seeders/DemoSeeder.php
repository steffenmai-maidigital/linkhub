<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Area;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo-Daten: Berger Maschinenbau — produzierende Unternehmensgruppe.
 * Bereiche -> Abteilungen und ein typischer Applikations-Katalog,
 * jeweils mit optionalen englischen Anzeigetexten; lokale Demo-Benutzer
 * admin/admin (Administration) und manager/manager (Inhaltspflege).
 * Idempotent: mehrfacher Start dupliziert nichts; leere EN-Felder
 * werden auf Bestandsdaten einmalig nachgezogen.
 *
 * Demo data: Berger Maschinenbau — a manufacturing group of companies.
 * Areas -> departments and a typical application catalogue,
 * each with optional English display texts; local demo users
 * admin/admin (administration) and manager/manager (content maintenance).
 * Idempotent: repeated starts duplicate nothing; empty EN fields
 * are filled in once on existing data.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['username' => 'admin'], [
            'name'        => 'Demo-Administrator',
            'auth_source' => User::LOCAL,
            'password'    => 'admin',
            'is_admin'    => true,
            'is_manager'  => true,
            'active'      => true,
        ]);
        User::updateOrCreate(['username' => 'manager'], [
            'name'        => 'Demo-Manager',
            'auth_source' => User::LOCAL,
            'password'    => 'manager',
            'is_admin'    => false,
            'is_manager'  => true,
            'active'      => true,
        ]);

        // [DE => [EN, [Abteilungen DE => EN]]]
        // [DE => [EN, [departments DE => EN]]]
        $areas = [
            'Verwaltung' => ['Administration', ['Personal' => 'Human Resources', 'Buchhaltung' => 'Accounting', 'Einkauf' => 'Purchasing']],
            'Produktion' => ['Production', ['Fertigung' => 'Manufacturing', 'Montage' => 'Assembly', 'Qualitätssicherung' => 'Quality Assurance']],
            'IT'         => ['IT', ['Support' => 'Support', 'Infrastruktur' => 'Infrastructure']],
        ];

        $areaModels = [];
        $deptModels = [];
        $sort = 0;
        foreach ($areas as $areaName => [$areaNameEn, $departments]) {
            $area = Area::firstOrCreate(['slug' => Str::slug($areaName)], [
                'name' => $areaName, 'name_en' => $areaNameEn, 'sort_order' => $sort += 10, 'active' => true,
            ]);
            $this->fillBlank($area, ['name_en' => $areaNameEn]);
            $areaModels[$areaName] = $area;

            $dSort = 0;
            foreach ($departments as $deptName => $deptNameEn) {
                $dept = Department::firstOrCreate(
                    ['area_id' => $area->id, 'slug' => Str::slug($deptName)],
                    ['name' => $deptName, 'name_en' => $deptNameEn, 'sort_order' => $dSort += 10, 'active' => true]
                );
                $this->fillBlank($dept, ['name_en' => $deptNameEn]);
                $deptModels[$areaName.'/'.$deptName] = $dept;
            }
        }

        $categories = [];
        $cSort = 0;
        foreach (['Kommunikation' => 'Communication', 'Organisation' => 'Organisation', 'Technik' => 'Technology', 'Personal & Verwaltung' => 'HR & Administration'] as $catName => $catNameEn) {
            $cat = Category::firstOrCreate(['name' => $catName], ['name_en' => $catNameEn, 'sort_order' => $cSort += 10]);
            $this->fillBlank($cat, ['name_en' => $catNameEn]);
            $categories[$catName] = $cat;
        }

        // [Name, Name EN, Beschreibung, Beschreibung EN, Kategorie, Bereiche, Abteilungen]
        // [name, name EN, description, description EN, category, areas, departments]
        $apps = [
            ['Intranet', null, 'Neuigkeiten, Speiseplan und schwarzes Brett der Unternehmensgruppe.', 'Company news, canteen menu and notice board of the group.', 'Kommunikation', [], []],
            ['Webmail', null, 'E-Mail-Zugriff über den Browser — auch von unterwegs.', 'E-mail access via the browser — also on the go.', 'Kommunikation', [], []],
            ['Chat', null, 'Direktnachrichten und Team-Kanäle für die schnelle Abstimmung.', 'Direct messages and team channels for quick coordination.', 'Kommunikation', [], []],
            ['Zeiterfassung', 'Time Tracking', 'Kommen/Gehen buchen, Salden und Urlaubskonto einsehen.', 'Clock in and out, view balances and your leave account.', 'Personal & Verwaltung', [], []],
            ['Urlaubsantrag', 'Leave Request', 'Urlaub und Gleittage digital beantragen und genehmigen.', 'Request and approve leave and flex days digitally.', 'Personal & Verwaltung', ['Verwaltung'], []],
            ['Reisekosten', 'Travel Expenses', 'Dienstreisen abrechnen, Belege hochladen.', 'Settle business trips, upload receipts.', 'Personal & Verwaltung', ['Verwaltung'], []],
            ['ERP-System', 'ERP System', 'Aufträge, Stücklisten und Lagerbestände der Produktion.', 'Orders, bills of materials and stock levels of production.', 'Organisation', ['Produktion'], ['Verwaltung/Einkauf', 'Verwaltung/Buchhaltung']],
            ['BDE-Terminal', 'Shop Floor Terminal', 'Betriebsdatenerfassung: Aufträge an- und abmelden.', 'Production data capture: start and finish orders.', 'Technik', [], ['Produktion/Fertigung', 'Produktion/Montage']],
            ['CAQ-Prüfplätze', 'CAQ Inspection', 'Prüfaufträge, Messwerte und Erstmusterprüfberichte.', 'Inspection orders, measurements and initial sample reports.', 'Technik', [], ['Produktion/Qualitätssicherung']],
            ['Ticketsystem', 'Ticket System', 'Störungen und Anfragen an die IT melden und verfolgen.', 'Report and track incidents and requests to IT.', 'Technik', [], []],
            ['Monitoring', null, 'Server- und Netzwerküberwachung mit Alarmierung.', 'Server and network monitoring with alerting.', 'Technik', ['IT'], []],
            ['DokuWiki', null, 'Interne Anleitungen, Prozesse und Systemdokumentation.', 'Internal guides, processes and system documentation.', 'Organisation', ['IT', 'Verwaltung'], []],
        ];

        foreach ($apps as $i => [$name, $nameEn, $desc, $descEn, $cat, $appAreas, $appDepts]) {
            $app = Application::firstOrCreate(['name' => $name], [
                'name_en'        => $nameEn,
                'description'    => $desc,
                'description_en' => $descEn,
                'url'            => 'https://beispiel.firma.local/'.Str::slug($name),
                'manual_url'     => $i % 2 === 0 ? 'https://beispiel.firma.local/wiki/'.Str::slug($name) : null,
                'category_id'    => $categories[$cat]->id,
                'sort_order'     => ($i + 1) * 10,
                'active'         => true,
            ]);
            $this->fillBlank($app, ['name_en' => $nameEn, 'description_en' => $descEn]);

            $app->areas()->syncWithoutDetaching(
                collect($appAreas)->map(fn ($n) => $areaModels[$n]->id)->all()
            );
            $app->departments()->syncWithoutDetaching(
                collect($appDepts)->map(fn ($n) => $deptModels[$n]->id)->all()
            );
        }
    }

    /**
     * Leere EN-Felder auf Bestandsdaten still nachziehen (ohne Audit-Einträge)
     *
     * Quietly fill in empty EN fields on existing data (without audit entries)
     */
    private function fillBlank($model, array $values): void
    {
        $dirty = [];
        foreach ($values as $key => $value) {
            if ($value !== null && blank($model->{$key})) {
                $dirty[$key] = $value;
            }
        }
        if ($dirty) {
            $model->forceFill($dirty)->saveQuietly();
        }
    }
}
