<?php

namespace Database\Seeders;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Uses project data from CSV: List of Warehouse - Projects.csv
     * Columns used: Name, Code
     * Start Date: 2026-01-01 (fixed)
     * End Date: 2029-01-01 (3 years in future, can be changed later)
     */
    public function run(): void
    {
        $startDate = Carbon::parse('2026-01-01');
        $endDate = Carbon::parse('2029-01-01');

        $projects = [
            ['code' => 'E660', 'name' => 'IGCF-2025 -JOINERY WORKS'],
            ['code' => 'P551', 'name' => 'SKY HILLS'],
            ['code' => 'FACTORY', 'name' => 'Factory'],
            ['code' => 'P411', 'name' => 'POET MAJLIS'],
            ['code' => 'P404', 'name' => 'AL DHAID WILDLIFE MUSEUM'],
            ['code' => 'P393', 'name' => 'ICESCO'],
            ['code' => 'P600', 'name' => 'MAJAZ AMP-THEATRE'],
            ['code' => 'P284', 'name' => 'AL HEERA HOUSE'],
            ['code' => 'P469', 'name' => 'BEACH VILLA'],
            ['code' => 'P465', 'name' => 'BEACH VILLA'],
            ['code' => 'P154', 'name' => 'SHARJAH LAKE MOSQUE'],
            ['code' => 'P336', 'name' => 'ALI BIN ABI TALIB MOSQUE'],
            ['code' => 'E614', 'name' => 'RAMADAN TENT - AL JADA-SHJ'],
            ['code' => 'P227', 'name' => 'HAFIA MOSQUE-DOORS'],
            ['code' => 'P142', 'name' => 'AL DHAID FORT'],
            ['code' => 'P392', 'name' => 'AL HAFIYA - CAFETERIA-2'],
            ['code' => 'P223', 'name' => 'DIBBA SOUQ'],
            ['code' => 'E607', 'name' => 'AL MAJAZ CONCERT 2025'],
            ['code' => 'P333', 'name' => 'AL HIYAR LAGOON'],
            ['code' => 'P366', 'name' => 'JAPANES RESTAURANT'],
            ['code' => 'E596', 'name' => 'XPOSURE 25- SHARJAH'],
            ['code' => 'E609', 'name' => 'MEDIA GATHERING-SHJ- 25'],
            ['code' => 'E590', 'name' => 'H.O.W - RUMI EXHIBITION-SHJ-24'],
            ['code' => 'E613', 'name' => 'HOW RAMADAN EVENT 2025'],
            ['code' => 'E612', 'name' => 'AL LAYYAH CANAL'],
            ['code' => 'P117', 'name' => 'EL YAS VILLA SHARJAH'],
            ['code' => 'P388', 'name' => 'HOLY QURAN ACADEMY - STAFF CAFETERIA'],
            ['code' => 'P525', 'name' => 'MINISTER VILLA'],
            ['code' => 'P011', 'name' => 'ENG. SALAH VILLA'],
            ['code' => 'P458', 'name' => 'TURKISH MEAT VAULT RESTAURANT'],
            ['code' => 'P222', 'name' => 'HAMAD VILLA - JOINERY WORKS'],
            ['code' => 'E633', 'name' => 'HYROX 2025 SHARJAH'],
            ['code' => 'E634', 'name' => 'KALILA DIMAN-HOUSE OF WISOM'],
            ['code' => 'I-De', 'name' => 'I-Develop Store'],
            ['code' => 'P578', 'name' => 'KALBA STADIUM PHASE 2'],
            ['code' => 'MORE', 'name' => 'MORELLA VILLA'],
            ['code' => 'P382', 'name' => 'FILI COFFEE'],
            ['code' => 'P577', 'name' => 'AL HEERA BUILDING 01 & 02'],
            ['code' => 'P442', 'name' => 'AL DHAID FISH GRILL RESTAURANT'],
            ['code' => 'P362', 'name' => 'SHARJAH GOVERNMENT MEDIA OFFICE'],
            ['code' => 'P541', 'name' => 'IRISE OFFICE - JOINERY WORKS'],
            ['code' => 'P648', 'name' => 'HOLY QURAN CONCEPT'],
            ['code' => 'E582', 'name' => 'MANUSCRIPT GALLERY - DISMANTLING WORK'],
            ['code' => 'P651', 'name' => 'ENGR.SALAH BARBER ROOM'],
            ['code' => 'P639', 'name' => 'HAMAD FARM HOUSE'],
            ['code' => 'P381', 'name' => 'FILI SOUQ - JOINERY WORKS'],
            ['code' => 'P636', 'name' => 'VILLA 95 ARABIAN RANCHES PERGOLA GYM'],
            ['code' => 'P617', 'name' => 'CALIDO RESTAURANT'],
            ['code' => 'P454', 'name' => 'LAFIF CAFETERIA'],
            ['code' => 'P662', 'name' => 'ENGR. SALAH VILLA - JOINERY WORKS'],
            ['code' => 'P352', 'name' => 'DOP - TRANSACTION DEPARTMENT'],
            ['code' => 'P642', 'name' => 'TCPM OFFICE NB6'],
            ['code' => 'P669', 'name' => 'MR. MOHAMMAD AL SARI'],
            ['code' => 'P287', 'name' => 'PRIVATE VILLA U-10'],
            ['code' => 'P623', 'name' => 'SHARJAH ISLAMIC BANK-8THFLOOR'],
            ['code' => 'P172', 'name' => 'STATISTIC OFFICE SHARJAH'],
            ['code' => 'P537', 'name' => 'SHEES VILLAGE'],
            ['code' => 'P380', 'name' => 'FILI ZOO'],
            ['code' => 'P552', 'name' => 'SKY HILLS ASTRA'],
            ['code' => 'E684', 'name' => 'MAHMOUD DARWISH-EXIBITION-SHUROOQ'],
            ['code' => 'P672', 'name' => 'SKY HILLS RECEPTION & LOBBY'],
            ['code' => 'P548', 'name' => 'MINSTR-VILLA-AUH-FF&E-G+1+L-SCPE'],
            ['code' => 'P350', 'name' => 'AL MADAM HOSPITAL'],
            ['code' => 'P513', 'name' => 'SKY HILLS -JVC/DXB/24'],
            ['code' => 'P585', 'name' => 'JAPANESE RESTAURANT- MAINTENACE-SHARJAH'],
            ['code' => 'E706', 'name' => 'SHARJAH EVENTS 2025'],
            ['code' => 'P195', 'name' => 'MUBADDARA OFFICE SHARJAH'],
            ['code' => 'E689', 'name' => 'SHARJAH XPOSURE-2026'],
            ['code' => 'P361', 'name' => 'FILI FORT - JOINERY WORKS'],
            ['code' => 'P664', 'name' => 'MINISTER VILLA'],
            ['code' => 'P239', 'name' => 'ABOVE THE CLOUDS RESTAURANT'],
            ['code' => 'P717', 'name' => 'AL OBEIDLY HOUSE - MAJLIS'],
            ['code' => 'E719', 'name' => 'RAMADAN TENT- ARADA SHARJAH'],
        ];

        foreach ($projects as $project) {
            Project::firstOrCreate(
                ['code' => $project['code']],
                [
                    'name' => $project['name'],
                    'code' => $project['code'],
                    'status' => 'active',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]
            );
        }

        $this->command->info('✅ ' . count($projects) . ' projects seeded successfully from CSV data!');
    }
}
