<?php

namespace App\Http\Controllers\Ghost;

use App\Appearance;
use App\DiscoverCategory;
use App\Helpers;
use App\Movie;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\App;
use App\Events\EventTrigger;

function array_insert(&$array, $value, $index)
{
    return $array = array_merge(array_splice($array, max(0, $index - 1)), array($value), $array);
}

class DiscoverController extends Controller
{

    /**
     * This mix of series and movies
     *
     * @return \Illuminate\Http\Response
     */
    public function getHomeResult()
    {

        // Movies Sorting by category
        $getMasByGenre = [];

        $discoverCategories = DiscoverCategory::get();


        // // Latest Movies
        // $latestMoviesQuery = DB::select('
        //               SELECT
        //               "movie" AS type,
        //               movies.m_id AS id,
        //               movies.m_name AS name,
        //               movies.m_poster AS poster,
        //               movies.m_desc AS overview,
        //               movies.m_runtime AS runtime,
        //               movies.m_year AS year,
        //               movies.m_genre AS genre,
        //               movies.m_rate AS rate,
        //               movies.m_backdrop AS backdrop,
        //               movies.m_age AS age,
        //               movies.m_cloud AS cloud,
        //               categories.name AS category_name,
        //               categories.kind AS category_type
        //               FROM movies
        //               JOIN categories ON categories.id = movies.m_category
        //               WHERE movies.m_age <> "G" AND movies.show <> 0
        //               GROUP BY movies.m_id
        //               ORDER BY movies.m_year DESC 
        //               LIMIT 25');


        // $latestSeriesQuery = DB::select('
        //               SELECT
        //               "series" AS type,
        //               series.t_id AS id,
        //               series.t_name AS name,
        //               series.t_desc AS overview,
        //               series.t_backdrop AS backdrop,
        //               series.t_genre AS genre,
        //               series.t_year AS year,
        //               series.t_rate AS rate,
        //               series.t_poster AS poster,
        //               series.t_age AS age,
        //               series.t_cloud AS cloud,
        //               CASE
        //               WHEN u4.series_id IS NULL OR u4.show = 0 THEN false
        //               ELSE true
        //               END AS "already_episode",
        //               categories.name AS category_name,
        //               categories.kind AS category_type
        //               FROM series
        //               JOIN categories ON categories.id = series.t_category
        //               LEFT JOIN episodes AS u4  ON u4.series_id = series.t_id
        //               WHERE series.t_age <> "G"
        //               GROUP BY series.t_id
        //               ORDER BY series.t_year DESC 
        //               LIMIT 25');

        // if (empty($latestMoviesQuery)) {
        //     $latestMoviesQuery = null;
        // } else {
        //     $getMasByGenre[] = [
        //         'list' => $latestMoviesQuery,
        //         'genre' => 'احدث الافلام',
        //         'type' => 'Movies'
        //     ];
        // }

        // if (empty($latestSeriesQuery)) {
        //     $latestSeriesQuery = null;
        // } else {
        //     $getMasByGenre[] = [
        //         'list' => $latestSeriesQuery,
        //         'genre' => 'احدث المسلسلات',
        //         'type' => 'Series'
        //     ];
        // }

        foreach ($discoverCategories as $category) {
            $query = [];
            if ($category->kind == 'movie') {
                $query = DB::select('
                    SELECT
                    "movie" AS type,
                    movies.m_id AS id,
                    movies.m_name AS name,
                    movies.m_poster AS poster,
                    movies.m_desc AS overview,
                    movies.m_runtime AS runtime,
                    movies.m_year AS year,
                    movies.m_genre AS genre,
                    movies.m_rate AS rate,
                    movies.m_backdrop AS backdrop,
                    movies.m_age AS age,
                    movies.m_cloud AS cloud,
                    categories.name AS category_name,
                    categories.kind AS category_type
                    FROM movies
                    JOIN categories ON categories.id = movies.m_category
                    WHERE movies.m_age <> "G" AND movies.show <> 0 AND movies.m_category = ' . $category->categorie_id . '
                    GROUP BY movies.m_id
                    ORDER BY movies.created_at DESC 
                    LIMIT 20');

                if (!empty($query)) {
                    $getMasByGenre[] = [
                        'list' => $query,
                        'genre' => $category->name,
                        'type' => 'Movies'
                    ];
                }
            } else if ($category->kind == 'series') {
                $query = DB::select('
                    SELECT
                    "series" AS type,
                    series.t_id AS id,
                    series.t_name AS name,
                    series.t_desc AS overview,
                    series.t_backdrop AS backdrop,
                    series.t_genre AS genre,
                    series.t_year AS year,
                    series.t_rate AS rate,
                    series.t_poster AS poster,
                    series.t_age AS age,
                    series.t_cloud AS cloud,
                    CASE
                    WHEN u4.series_id IS NULL OR u4.show = 0 THEN false
                    ELSE true
                    END AS "already_episode",
                    categories.name AS category_name,
                    categories.kind AS category_type
                    FROM series
                    JOIN categories ON categories.id = series.t_category
                    LEFT JOIN episodes AS u4  ON u4.series_id = series.t_id
                    WHERE series.t_age <> "G" AND series.t_category = ' . $category->categorie_id . '
                    GROUP BY series.t_id
                    ORDER BY series.created_at DESC 
                    LIMIT 20');

                if (!empty($query)) {
                    $getMasByGenre[] = [
                        'list' => $query,
                        'genre' => $category->name,
                        'type' => 'Series'
                    ];
                }
            }
        }




        // Get top movies and series
        $getTopMas = DB::select('(SELECT
                                "movie" AS type,
                                movies.m_id AS id,
                                movies.m_name AS name,
                                movies.m_poster AS poster,
                                movies.m_desc AS overview,
                                movies.m_year AS year,
                                movies.m_genre AS genre,
                                movies.m_rate AS rate,
                                movies.m_backdrop AS backdrop,
                                movies.m_poster AS poster,
                                movies.m_age AS age,
                                movies.m_cloud AS cloud
                                FROM tops
                                INNER JOIN movies  ON movies.m_id = tops.movie_id
                                GROUP BY movies.m_id DESC)
                                UNION
                                (SELECT
                                "series" AS type,
                                series.t_id AS id,
                                series.t_name AS name,
                                series.t_poster AS poster,
                                series.t_desc AS overview,
                                series.t_year AS year,
                                series.t_genre AS genre,
                                series.t_rate AS rate,
                                series.t_backdrop AS backdrop,
                                series.t_poster AS poster,
                                series.t_age AS age,
                                series.t_cloud AS cloud
                                FROM tops
                        	    INNER JOIN series  ON series.t_id = tops.series_id
                                LEFT JOIN episodes AS u4  ON u4.series_id = series.t_id
                                GROUP BY series.t_id DESC)');
        if (empty($getTopMas)) {
            $getTopMas = null;
        }


        return response()->json([
            'status' => 'success',
            'data' => [
                'data' => $getMasByGenre,
                'top' => $getTopMas,
            ]
        ], 200);
    }

    /**
     * Get Notifcation
     *
     * @return void
     */
    public function getNotifaction()
    {

        // Get support

        $getSupport = DB::table('supports')
            ->selectRaw('supports.*, support_responses.readit, support_responses.reply')
            ->leftJoin('support_responses', function ($join) {
                $join->on('support_responses.request_id', '=', 'supports.request_id')
                    ->where('support_responses.from', '=', 'support');
            })
            ->where('supports.uid', Auth::id())
            ->where('support_responses.readit', 1)
            ->groupBy('supports.id')
            ->get();

        if ($getSupport->isEmpty()) {
            $getSupport = null;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'support' => $getSupport
            ]
        ], 200);
    }
}
