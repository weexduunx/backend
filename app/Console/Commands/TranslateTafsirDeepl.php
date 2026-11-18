<?php

namespace App\Console\Commands;

use App\Models\TafsirTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Traduit en français les commentaires pas encore traduits, via DeepL.
 * Peut être relancée sans risque : elle reprend là où elle s'est arrêtée
 * et s'arrête proprement quand le quota mensuel est épuisé.
 */
class TranslateTafsirDeepl extends Command
{
    protected $signature = 'tafsir:translate
        {--source=muyassar}
        {--max-chars=0 : Arrêter après ce nombre de caractères (0 = jusqu\'au quota)}
        {--dry-run : Affiche seulement ce qui reste à traduire}';

    protected $description = 'Traduit en français les tafsirs importés (DeepL)';

    private const BATCH = 25;

    public function handle(): int
    {
        $pending = TafsirTranslation::where('source', $this->option('source'))
            ->where('language', 'fr')
            ->whereNull('text_translated')
            ->orderBy('surah')->orderBy('ayah');

        $count = (clone $pending)->count();
        $chars = (clone $pending)->pluck('text_original')->sum(fn ($t) => mb_strlen($t));
        $this->info("À traduire : {$count} commentaires, {$chars} caractères.");
        if ($this->option('dry-run') || $count === 0) {
            return self::SUCCESS;
        }

        $key = config('services.deepl.key');
        if (! $key) {
            $this->error('DEEPL_API_KEY manquante dans .env');

            return self::FAILURE;
        }
        $base = str_ends_with($key, ':fx') ? 'https://api-free.deepl.com' : 'https://api.deepl.com';
        $http = fn () => Http::withHeaders(['Authorization' => "DeepL-Auth-Key {$key}"])->timeout(60)->retry(3, 3000, throw: false);

        $usage = $http()->get("{$base}/v2/usage");
        if ($usage->ok()) {
            $left = $usage->json('character_limit') - $usage->json('character_count');
            $this->info("Quota DeepL restant ce mois-ci : {$left} caractères.");
        }

        $maxChars = (int) $this->option('max-chars');
        $done = 0;
        $sent = 0;

        foreach ($pending->lazyById(self::BATCH * 4)->chunk(self::BATCH) as $rows) {
            // chunk() d'une LazyCollection renvoie une LazyCollection : accès par index impossible
            $rows = $rows->values()->collect();
            $batchChars = $rows->sum(fn ($r) => mb_strlen($r->text_original));
            if ($maxChars && $sent + $batchChars > $maxChars) {
                break;
            }

            $res = $http()->asJson()->post("{$base}/v2/translate", [
                'text' => $rows->pluck('text_original')->all(),
                'source_lang' => 'AR',
                'target_lang' => 'FR',
                'context' => "Commentaire coranique (tafsir) d'At-Tafsir Al-Muyassar. Allah, les prophètes et les termes religieux doivent garder leur sens islamique.",
                'formality' => 'more',
            ]);

            if ($res->status() === 456) {
                $this->warn('Quota DeepL du mois épuisé. Relancez la commande le mois prochain.');
                break;
            }
            if (! $res->ok()) {
                $this->error("Erreur DeepL HTTP {$res->status()} : ".$res->body());

                return self::FAILURE;
            }

            $translations = $res->json('translations', []);
            if (count($translations) !== $rows->count()) {
                $this->error('Réponse DeepL incomplète : '.count($translations).' traductions pour '.$rows->count().' textes.');

                return self::FAILURE;
            }

            foreach ($translations as $i => $t) {
                $rows[$i]->update([
                    'text_translated' => $t['text'],
                    'translator' => 'deepl',
                    'translated_at' => now(),
                ]);
                $done++;
            }
            $sent += $batchChars;
            $this->line("  {$done}/{$count} traduits ({$sent} caractères)");
        }

        $this->info("Terminé pour cette fois : {$done} commentaires traduits, ".($count - $done).' restants.');

        return self::SUCCESS;
    }
}
