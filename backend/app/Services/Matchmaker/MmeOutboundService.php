<?php

namespace App\Services\Matchmaker;

use App\Models\DiagnosticOdyssey;
use App\Models\MmeMatch;
use App\Models\MmePeer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MmeOutboundService
{
    public function __construct(private MmeProfileSerializer $serializer) {}

    public function searchForOdyssey(DiagnosticOdyssey $odyssey): int
    {
        $profile = $this->serializer->serialize($odyssey);
        $created = 0;

        foreach (MmePeer::query()->active()->outbound()->get() as $peer) {
            try {
                $response = Http::timeout(20)
                    ->withBody(json_encode($profile), 'application/vnd.ga4gh.matchmaker.v1.0+json')
                    ->withHeaders([
                        'X-Auth-Token' => $peer->auth_token,
                        'Accept' => 'application/vnd.ga4gh.matchmaker.v1.0+json',
                    ])
                    ->post(rtrim($peer->base_url, '/').'/match');

                if (! $response->successful()) {
                    Log::warning('MME outbound non-success', [
                        'peer_id' => $peer->id,
                        'status' => $response->status(),
                    ]);

                    continue;
                }

                foreach ($response->json('results') ?? [] as $result) {
                    $patientId = $result['patient']['id'] ?? null;

                    // Dedupe: skip if an identical remote patient is already stored for this odyssey+peer
                    if (MmeMatch::where('odyssey_id', $odyssey->id)
                        ->where('peer_id', $peer->id)
                        ->where('matched_profile->id', $patientId)
                        ->exists()) {
                        continue;
                    }

                    MmeMatch::create([
                        'odyssey_id' => $odyssey->id,
                        'peer_id' => $peer->id,
                        'direction' => 'outbound',
                        'score' => (float) ($result['score']['patient'] ?? 0),
                        'matched_label' => $result['patient']['label'] ?? null,
                        'matched_contact_name' => $result['patient']['contact']['name'] ?? null,
                        'matched_contact_href' => $result['patient']['contact']['href'] ?? null,
                        'matched_profile' => $result['patient'] ?? [],
                        'status' => 'new',
                    ]);

                    $created++;
                }
            } catch (\Throwable $e) {
                Log::warning('MME outbound error', [
                    'peer_id' => $peer->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        return $created;
    }
}
