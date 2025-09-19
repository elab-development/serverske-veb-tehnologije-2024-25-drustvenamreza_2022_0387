<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminExternalStatsController extends Controller
{
    private function ensureAdmin(Request $request)
    {
        $auth = $request->user();
        if (!$auth) {
            abort(response()->json(['message' => 'Unauthorized'], 401));
        }
        if (!$auth->isAdmin()) {
            abort(response()->json(['message' => 'Forbidden'], 403));
        }
    }

    public function hnPopularKeywords(Request $request)
    {
        $this->ensureAdmin($request);

        $hours = max(1, min(72, (int)$request->query('hours', 24)));
        $minPoints = max(0, (int)$request->query('min_points', 50));
        $pages = max(1, min(5, (int)$request->query('pages', 2)));
        $hitsPer = 100;

        $since = now()->subHours($hours)->timestamp;
        $stop = collect([
            'the',
            'a',
            'an',
            'and',
            'or',
            'for',
            'to',
            'of',
            'in',
            'on',
            'at',
            'by',
            'is',
            'are',
            'be',
            'with',
            'from',
            'this',
            'that',
            'into',
            'it',
            'its',
            'as',
            'we',
            'you',
            'i',
            'your',
            'our',
            'their',
            'about',
            'how',
            'why',
            'what',
            'when',
            'where',
            'who'
        ]);

        $keywords = [];
        $fetched = 0;

        for ($page = 0; $page < $pages; $page++) {
            $resp = Http::get('https://hn.algolia.com/api/v1/search_by_date', [
                'tags' => 'story',
                'hitsPerPage' => $hitsPer,
                'page' => $page,
                'numericFilters' => "created_at_i>{$since},points>={$minPoints}",
            ]);

            if (!$resp->ok()) {
                return response()->json(['message' => 'HN Algolia error', 'status' => $resp->status()], 502);
            }

            foreach ((array)$resp->json('hits') as $hit) {
                $title = (string)($hit['title'] ?? '');
                if ($title === '') continue;
                $fetched++;

                $tokens = preg_split('/[^A-Za-z0-9]+/', strtolower($title), -1, PREG_SPLIT_NO_EMPTY);
                foreach ($tokens as $t) {
                    if (strlen($t) < 3) continue;
                    if ($stop->contains($t)) continue;
                    $keywords[$t] = ($keywords[$t] ?? 0) + 1;
                }
            }
        }

        arsort($keywords);
        $top = collect($keywords)->take(50)->map(function ($count, $word) {
            return ['tag' => "#" . preg_replace('/[^A-Za-z0-9]+/', '', $word), 'word' => $word, 'count' => $count];
        })->values()->all();

        return response()->json([
            'window_hours' => $hours,
            'min_points' => $minPoints,
            'stories_seen' => $fetched,
            'popular_tags' => $top,
        ]);
    }
    public function guardianPopularTags(Request $request)
    {
        $this->ensureAdmin($request);

        $apiKey = config('services.guardian.key') ?: env('GUARDIAN_API_KEY');
        if (!$apiKey) {
            return response()->json(['message' => 'Missing Guardian API key'], 500);
        }

        $q   = $request->query('q');
        $from  = $request->query('from', now()->subDays(7)->toDateString());
        $to   = $request->query('to', now()->toDateString());
        $pages   = max(1, min(5, (int) $request->query('pages', 2)));
        $pageSz  = 50;

        $tagCounts = [];
        $fetched   = 0;

        for ($page = 1; $page <= $pages; $page++) {
            $resp = Http::get('https://content.guardianapis.com/search', [
                'q' => $q,
                'from-date' => $from,
                'to-date' => $to,
                'order-by' => 'newest',
                'page-size' => $pageSz,
                'page' => $page,
                'show-tags' => 'keyword',
                'api-key' => $apiKey,
            ]);

            if (!$resp->ok()) {
                return response()->json([
                    'message' => 'Guardian API error',
                    'status' => $resp->status(),
                ], 502);
            }

            $json = $resp->json();
            $results = data_get($json, 'response.results', []);
            $fetched += count($results);

            foreach ($results as $item) {
                foreach ($item['tags'] ?? [] as $tag) {
                    if (($tag['type'] ?? '') !== 'keyword') continue;
                    $id    = $tag['id'] ?? null;
                    $title = $tag['webTitle'] ?? null;
                    if (!$id || !$title) continue;

                    if (!isset($tagCounts[$id])) {
                        $tagCounts[$id] = ['id' => $id, 'title' => $title, 'count' => 0];
                    }
                    $tagCounts[$id]['count']++;
                }
            }
            $current = data_get($json, 'response.currentPage', $page);
            $pagesTotal = data_get($json, 'response.pages', $page);
            if ($current >= $pagesTotal) break;
        }

        $popular = collect($tagCounts)
            ->sortByDesc('count')
            ->values()
            ->all();

        return response()->json([
            'query' => $q,
            'from' => $from,
            'to' => $to,
            'articles_seen' => $fetched,
            'tags' => $popular,
        ]);
    }
}
