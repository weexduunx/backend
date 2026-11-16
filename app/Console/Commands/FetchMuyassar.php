<?php

namespace App\Console\Commands;

use App\Models\TafsirTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Importe le texte arabe d'At-Tafsir Al-Muyassar (Quran.com, ressource 16)
 * dans tafsir_translations, sans écraser les traductions déjà faites.
 */
class FetchMuyassar extends Command
{
    protected $signature = 'tafsir:fetch-muyassar {--surah= : Une seule sourate}';
    protected $description = "Importe le texte arabe d'At-Tafsir Al-Muyassar depuis Quran.com";

    private const RESOURCE_ID = 16;

    public function handle(): int
    {
        $surahs = $this->option('surah') ? [(int) $this->option('surah')] : range(1, 114);
        $bar = $this->output->createProgressBar(count($surahs));
        $total = 0;

        foreach ($surahs as $surah) {
            $page = 1;
            do {
                $res = Http::retry(3, 2000)->timeout(30)->get(
                    'https://api.quran.com/api/v4/tafsirs/'.self::RESOURCE_ID."/by_chapter/{$surah}",
                    ['per_page' => 50, 'page' => $page]
                );
                if (! $res->ok()) {
                    $this->error("Sourate {$surah} page {$page} : HTTP {$res->status()}");

                    return self::FAILURE;
                }
                foreach ($res->json('tafsirs', []) as $t) {
                    [$s, $a] = array_map('intval', explode(':', $t['verse_key']));
                    $text = trim(html_entity_decode(strip_tags($t['text'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if ($text === '') {
                        continue;
                    }
                    $row = TafsirTranslation::firstOrNew([
                        'source' => 'muyassar', 'language' => 'fr', 'surah' => $s, 'ayah' => $a,
                    ]);
                    // Texte modifié à la source : la traduction est à refaire
                    if ($row->exists && $row->text_original !== $text) {
                        $row->text_translated = null;
                        $row->translated_at = null;
                    }
                    $row->verse_key = $t['verse_key'];
                    $row->text_original = $text;
                    $row->save();
                    $total++;
                }
                $page = $res->json('pagination.next_page');
            } while ($page);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$total} commentaires importés.");

        return self::SUCCESS;
    }
}
