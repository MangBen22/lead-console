<?php

namespace LC;

if (!defined('ABSPATH')) {
    exit;
}

class Connectors
{
    public static function discover(string $provider, array $params, int $limit): array
    {
        if ($provider !== 'google_places') {
            return [];
        }

        return self::google_places($params, $limit);
    }

    private static function google_places(array $params, int $limit): array
    {
        $settings = Settings::get();
        $query = trim((string) ($params['query'] ?? ''));
        $city = trim((string) ($params['city'] ?? ''));

        if ($query === '') {
            return [];
        }

        if (empty($settings['enable_live_apis']) || empty($settings['google_places_api_key'])) {
            return [];
        }

        $textQuery = trim($query . ' ' . $city);

        $endpoint = add_query_arg(
            [
                'query' => $textQuery,
                'key' => $settings['google_places_api_key'],
            ],
            'https://maps.googleapis.com/maps/api/place/textsearch/json'
        );

        $resp = wp_remote_get($endpoint, ['timeout' => 15]);
        if (is_wp_error($resp)) {
            return [];
        }

        $code = (int) wp_remote_retrieve_response_code($resp);
        if ($code !== 200) {
            return [];
        }

        $body = json_decode((string) wp_remote_retrieve_body($resp), true);
        if (!is_array($body) || empty($body['results']) || !is_array($body['results'])) {
            return [];
        }

        $results = [];
        foreach (array_slice($body['results'], 0, $limit) as $row) {
            $results[] = [
                'business_name' => (string) ($row['name'] ?? ''),
                'city' => $city,
                'category' => (string) (($row['types'][0] ?? '') ?: ''),
                'address' => (string) ($row['formatted_address'] ?? ''),
                'website' => '',
                'phone' => '',
                'email' => '',
                'review_count' => (int) ($row['user_ratings_total'] ?? 0),
                'rating' => (float) ($row['rating'] ?? 0),
                'source_url' => '',
                'status' => 'new',
            ];
        }

        return $results;
    }
}
