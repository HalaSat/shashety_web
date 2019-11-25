<?php

namespace App\Http\Controllers\Users;

use App\Episode;
use App\Helpers;
use App\Http\Controllers\Controller;
use App\Movie;
use App\Recently_watched;
use App\Report;
use App\Series;
use App\Subtitle;
use App\Video;
use App\Live_tv;
use Carbon\Carbon;
use App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Aws\CloudFront\CloudFrontClient;

class VideoPlayerControllerNoAuth extends Controller
{
    /**
     * Get Episode Video
     *
     * @param Request $request
     * @return void
     */
    public function getEpisodeVideo(Request $request)
    {
        $request->validate([
            'episode_id' => 'nullable|uuid',
            'series_id' => 'nullable|uuid',
        ]);





        if ($request->has('series_id') && !$request->has('episode_id')) {
            // Check if the series is exist already
            $checkAlreadySeries = Series::where('t_id', $request->input('series_id'))->first();
            $getFirstSeason = Episode::where('series_id', $request->input('series_id'))->first();

            if (is_null($checkAlreadySeries)) {
                return response()->json(['status' => 404], 404);
            }

            // Get Season One
            $getSeasonOne = DB::table('episodes')
                ->selectRaw('episodes.*, recently_watcheds.current_time, recently_watcheds.duration_time')
                ->leftJoin('recently_watcheds', function ($join) {
                    $join->on('recently_watcheds.episode_id', '=', 'episodes.id')
                        ->where('recently_watcheds.uid', '=', Auth::id());
                })
                ->where('episodes.series_id', $request->input('series_id'))
                ->where('episodes.season_number', $getFirstSeason->season_number)
                ->where('episodes.show', '<>', 0)
                ->orderBy('episode_number', 'ASC')
                ->get();




            // Get All Seasons
            $getAllSeason = DB::table('episodes')
                ->select('season_number', 'series_id')
                ->where('series_id', $request->input('series_id'))
                ->where('show', '<>', 0)
                ->groupBy('season_number')
                ->orderBy('season_number', 'ASC')
                ->get();


            // SORT
            $playlist = [];

            // Check if there is no cast
            if ($getSeasonOne->isEmpty()) {
                $season = null;
            } else {

                // Sort season and episode
                foreach ($getSeasonOne as $key => $value) {
                    $getVideo = Video::select('video_format', 'video_cloud', 'resolution', 'video_url')->where('episode_id', $value->id)->get();
                    $getSubtitle = Subtitle::where('episode_id', $value->id)->get();

                    $videoArray = [];
                    $subArray = [];

                    if ($getVideo->isEmpty()) {
                        $getVideo = null;
                    } else {
                        foreach ($getVideo as $key => $videoValue) {
                            if ($videoValue->video_format == 'hls') {
                                if ($videoValue->video_cloud === 'local') {
                                    $videoArray[$key]['file'] = url('/') . $videoValue->video_url;
                                } elseif ($videoValue->video_cloud === 'link') {
                                    $videoArray[$key]['file'] = $videoValue->video_url;
                                } else {
                                    $videoArray[$key]['file'] = config('aws.cloudfront_private_url') . '/' . $videoValue->video_url;
                                }

                                $videoArray[$key]['type'] = 'application/vnd.apple.mpegurl';
                            } elseif ($videoValue->video_format == 'mp4') {
                                if ($videoValue->video_cloud === 'local') {
                                    $videoArray[$key]['file'] = url('/') . $videoValue->video_url;
                                } elseif ($videoValue->video_cloud === 'link') {
                                    $videoArray[$key]['file'] = $videoValue->video_url;
                                } else {
                                    $videoArray[$key]['file'] = config('aws.cloudfront_private_url') . '/' . $videoValue->video_url;
                                }

                                if ($videoValue->resolution == '4k') {
                                    $videoArray[$key]['label'] = '4K';
                                } else {
                                    $videoArray[$key]['label'] = $videoValue->resolution . 'p';
                                }
                                $videoArray[$key]['type'] = 'video/mp4';
                            } else {
                                if ($videoValue->resolution == '4k') {
                                    $videoArray[$key]['label'] = '4K';
                                } else {
                                    $videoArray[$key]['label'] = $videoValue->resolution . 'p';
                                }
                                $videoArray[$key]['file'] = $videoValue->video_url;
                            }
                        }
                    }


                    if ($getSubtitle->isEmpty()) {
                        $getSubtitle = null;
                    } else {
                        foreach ($getSubtitle as $key => $subValue) {
                            $subArray[] = [
                                'file' => url('/') . $subValue->name,
                                'label' => ucwords($subValue->language),
                                "kind" => "captions",
                                "default" => ucwords($subValue->language) == "ARABIC"
                            ];
                        }
                    }

                    if ($value->cloud === 'local') {
                        $value->backdrop = url('/') . '/storage/backdrops/300_' . $value->backdrop;
                    } else {
                        $value->backdrop = config('aws.cloudfront_public_url') . '/backdrops/' . $value->backdrop;
                    }

                    $playlist[] = [
                        'title' => $value->name,
                        'VideoNumber' => $value->episode_number,
                        'description' => $value->overview,
                        'image' => $value->backdrop,
                        'sources' => $videoArray,
                        'tracks' => $subArray,
                        'withCredentials' => true
                    ];
                }
            }


            $seasonArray = [];
            if ($getSeasonOne->isEmpty()) {
                $seasonArray = null;
            } else {
                foreach ($getSeasonOne as $key => $value) {
                    array_push($seasonArray, $value);
                }
            }


            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        'playlist' => [
                            'playlist' => $playlist
                        ],
                        'series' => $checkAlreadySeries,
                        'seasons' => $getAllSeason,
                        'current_season' => $seasonArray,
                        'current_episode' => $seasonArray[0],
                        'geo_status' => true

                    ]
                ]
            );
        }


        // Get Episode When Click To Episode
        if ($request->has('series_id') && $request->has('episode_id')) {

            // Check if the series is exist already
            $checkAlreadyEpisode = Episode::select('series.t_name AS name', 'episodes.*')
                ->join('series', 'series.t_id', 'episodes.series_id')
                ->where('episodes.id', $request->input('episode_id'))
                ->first();

            if (is_null($checkAlreadyEpisode)) {
                return response()->json(['status' => 404], 404);
            }

            // Get Season One
            $getSeasonOne = DB::table('episodes')
                ->selectRaw('episodes.*, recently_watcheds.current_time, recently_watcheds.duration_time')
                ->leftJoin('recently_watcheds', function ($join) {
                    $join->on('recently_watcheds.episode_id', '=', 'episodes.id')
                        ->where('recently_watcheds.uid', '=', Auth::id());
                })
                ->where('episodes.series_id', $request->input('series_id'))
                ->where('episodes.season_number', $checkAlreadyEpisode->season_number)
                ->where('episodes.show', '<>', 0)
                ->orderBy('episode_number', 'ASC')
                ->get();

            $getAllSeason = DB::table('episodes')
                ->selectRaw('season_number, series_id')
                ->where('series_id', $request->input('series_id'))
                ->where('show', '<>', 0)
                ->groupBy('season_number')
                ->orderBy('season_number', 'ASC')
                ->get();

            // SORT

            $season = [];
            $playlist = [];
            $current_episode = null;

            // Check if there is no cast
            if ($getSeasonOne->isEmpty()) {
                $season = null;
            } else {

                // Sort season and episode
                foreach ($getSeasonOne as $key => $value) {
                    $getVideo = Video::select('video_format', 'video_cloud', 'resolution', 'video_url')->where('episode_id', $value->id)->get();
                    $getSubtitle = Subtitle::where('episode_id', $value->id)->get();
                    $videoArray = [];
                    $subArray = [];


                    if ($getVideo->isEmpty()) {
                        $getVideo = null;
                    } else {
                        foreach ($getVideo as $key => $videoValue) {
                            if ($videoValue->video_format == 'hls') {
                                if ($videoValue->video_cloud === 'local') {
                                    $videoArray[$key]['file'] = url('/') . $videoValue->video_url;
                                } elseif ($videoValue->video_cloud === 'link') {
                                    $videoArray[$key]['file'] = $videoValue->video_url;
                                } else {
                                    $videoArray[$key]['file'] = config('aws.cloudfront_private_url') . '/' . $videoValue->video_url;
                                }

                                $videoArray[$key]['type'] = 'application/vnd.apple.mpegurl';
                            } elseif ($videoValue->video_format == 'mp4') {

                                if ($videoValue->video_cloud === 'local') {
                                    $videoArray[$key]['file'] = url('/') . $videoValue->video_url;
                                } elseif ($videoValue->video_cloud === 'link') {
                                    $videoArray[$key]['file'] = $videoValue->video_url;
                                } else {
                                    $videoArray[$key]['file'] = config('aws.cloudfront_private_url') . '/' . $videoValue->video_url;
                                }

                                if ($videoValue->resolution == '4k') {
                                    $videoArray[$key]['label'] = '4K';
                                } else {
                                    $videoArray[$key]['label'] = $videoValue->resolution . 'p';
                                }
                                $videoArray[$key]['type'] = 'video/mp4';
                            } else {
                                if ($videoValue->resolution == '4k') {
                                    $videoArray[$key]['label'] = '4K';
                                } else {
                                    $videoArray[$key]['label'] = $videoValue->resolution . 'p';
                                }
                                $videoArray[$key]['file'] = $videoValue->video_url;
                            }
                        }
                    }


                    if ($getSubtitle->isEmpty()) {
                        $getSubtitle = null;
                    } else {
                        foreach ($getSubtitle as $key => $subValue) {
                            $subArray[] = [
                                'file' => url('/') . $subValue->name,
                                'label' => ucwords($subValue->language),
                                "kind" => "captions",
                                "default" => ucwords($subValue->language) == "ARABIC"
                            ];
                        }
                    }

                    if ($value->cloud === 'local') {
                        $value->backdrop = url('/') . '/storage/backdrops/300_' . $value->backdrop;
                    } else {
                        $value->backdrop = config('aws.cloudfront_public_url') . '/backdrops/' . $value->backdrop;
                    }

                    if ($value->id == $request->input('episode_id')) {
                        $current_episode = $value;
                        $playlist[0] = [
                            'title' => $value->name,
                            'VideoNumber' => $value->episode_number,
                            'description' => $value->overview,
                            'image' => $value->backdrop,
                            'sources' => $videoArray,
                            'tracks' => $subArray,
                            'withCredentials' => true

                        ];
                    } else {
                        $playlist[] = [
                            'title' => $value->name,
                            'VideoNumber' => $value->episode_number,
                            'description' => $value->overview,
                            'image' => $value->backdrop,
                            'sources' => $videoArray,
                            'tracks' => $subArray,
                            'withCredentials' => true

                        ];
                    }
                }
            }

            $seasonArray = [];
            if ($getSeasonOne->isEmpty()) {
                $seasonArray = null;
            } else {
                foreach ($getSeasonOne as $key => $value) {
                    array_push($seasonArray, $value);
                }
            }


            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        'playlist' => [
                            'playlist' => $playlist
                        ],
                        'series' => $checkAlreadyEpisode,
                        'seasons' => $getAllSeason,
                        'current_season' => $seasonArray,
                        'current_episode' => $current_episode,
                        'geo_status' => true

                    ]
                ]
            );
        }


        // Get Episode When Click To Episode
        if ($request->has('series_id') && $request->has('episode_id')) {

            // Check if the series is exist already
            $checkAlreadyEpisode = Episode::select('series.t_name AS name', 'episodes.season_id')
                ->join('series', 'series.t_id', 'episodes.series_id')
                ->where('episodes.id', $request->input('episode_id'))
                ->first();

            if (is_null($checkAlreadyEpisode)) {
                return response()->json(['status' => 404], 404);
            }

            // Get Season One
            $getSeasonOne = DB::table('episodes')
                ->selectRaw('episodes.*, recently_watcheds.current_time, recently_watcheds.duration_time')
                ->leftJoin('recently_watcheds', function ($join) {
                    $join->on('recently_watcheds.episode_id', '=', 'episodes.id')
                        ->where('recently_watcheds.uid', '=', Auth::id());
                })
                ->where('episodes.series_id', $request->input('series_id'))
                ->where('episodes.season_number', $checkAlreadyEpisode->season_number)
                ->where('episodes.show', '<>', 0)
                ->orderByRaw('episodes.season_number, episode_number + 0 ASC')
                ->get();


            $getAllSeason = DB::table('episodes')
                ->selectRaw('season_number, series_id')
                ->where('series_id', $request->input('series_id'))
                ->where('show', '<>', 0)
                ->groupBy('season_number')
                ->orderBy('season_number', 'ASC')
                ->get();

            // SORT

            $season = [];
            $playlist = [];
            $current_episode = null;

            // Check if there is no cast
            if ($getSeasonOne->isEmpty()) {
                $season = null;
            } else {

                // Sort season and episode
                foreach ($getSeasonOne as $key => $value) {
                    $getVideo = Video::select('video_format', 'video_cloud', 'resolution', 'video_url')->where('episode_id', $value->id)->get();
                    $getSchedulesAds = DB::table('ads_schedules')
                        ->select('ads_schedules.offset', 'ads_schedules.id AS schedules_id', 'ads.id AS ads_id', 'ads_schedules.episode_id', 'ads.ad_name', 'ads.client', 'ads.tags')
                        ->join('ads', 'ads.id', '=', 'ads_schedules.ads_id')
                        ->where('ads_schedules.episode_id', $value->id)
                        ->get();

                    $getSubtitle = Subtitle::where('episode_id', $value->id)->get();
                    $videoArray = [];
                    $adsArray = [];
                    $subArray = [];


                    if ($getVideo->isEmpty()) {
                        $getVideo = null;
                    } else {
                        foreach ($getVideo as $key => $videoValue) {
                            if ($videoValue->video_format == 'hls') {
                                if ($videoValue->video_cloud === 'local') {
                                    $videoArray[$key]['file'] = url('/') . $videoValue->video_url;
                                } elseif ($videoValue->video_cloud === 'link') {
                                    $videoArray[$key]['file'] = $videoValue->video_url;
                                } else {
                                    $videoArray[$key]['file'] = config('aws.cloudfront_private_url') . '/' . $videoValue->video_url;
                                }

                                $videoArray[$key]['type'] = 'application/vnd.apple.mpegurl';
                            } elseif ($videoValue->video_format == 'mp4') {
                                if ($videoValue->video_cloud === 'local') {
                                    $videoArray[$key]['file'] = url('/') . $videoValue->video_url;
                                } elseif ($videoValue->video_cloud === 'link') {
                                    $videoArray[$key]['file'] = $videoValue->video_url;
                                } else {
                                    $videoArray[$key]['file'] = config('aws.cloudfront_private_url') . '/' . $videoValue->video_url;
                                }

                                if ($videoValue->resolution == '4k') {
                                    $videoArray[$key]['label'] = '4K';
                                } else {
                                    $videoArray[$key]['label'] = $videoValue->resolution . 'p';
                                }
                                $videoArray[$key]['type'] = 'video/mp4';
                            }
                        }
                    }

                    if ($getSchedulesAds->isEmpty()) {
                        $getSchedulesAds = null;
                    } else {
                        foreach ($getSchedulesAds as $key => $adsValue) {
                            $adsArray[$key] = [
                                'offset' => $adsValue->offset . '%',
                                'tag' => $adsValue->tags
                            ];
                        }
                    }

                    if ($getSubtitle->isEmpty()) {
                        $getSubtitle = null;
                    } else {
                        foreach ($getSubtitle as $key => $subValue) {
                            $subArray[] = [
                                'file' => url('/') . $subValue->name,
                                'label' => ucwords($subValue->language),
                                "kind" => "captions",
                                "default" => ucwords($subValue->language) == "ARABIC"
                            ];
                        }
                    }

                    if ($value->cloud === 'local') {
                        $value->backdrop = url('/') . '/storage/backdrops/300_' . $value->backdrop;
                    } else {
                        $value->backdrop = config('aws.cloudfront_public_url') . '/backdrops/' . $value->backdrop;
                    }


                    if ($value->show == 3) {
                        if (Carbon::now() >= $value->publish_date && Carbon::now() <= $value->expire_date) {
                            if ($value->id == $request->input('episode_id')) {
                                $current_episode = $value;
                                $playlist[0] = [
                                    'title' => $value->name,
                                    'VideoNumber' => $value->episode_number,
                                    'description' => $value->overview,
                                    'image' => $value->backdrop,
                                    'adschedule' => $adsArray,
                                    'sources' => $videoArray,
                                    'tracks' => $subArray,
                                    'withCredentials' => true

                                ];
                            } else {
                                $playlist[] = [
                                    'title' => $value->name,
                                    'VideoNumber' => $value->episode_number,
                                    'description' => $value->overview,
                                    'image' => $value->backdrop,
                                    'adschedule' => $adsArray,
                                    'sources' => $videoArray,
                                    'tracks' => $subArray,
                                    'withCredentials' => true

                                ];
                            }
                        }
                    } else {
                        if ($value->id == $request->input('episode_id')) {
                            $current_episode = $value;
                            $playlist[0] = [
                                'title' => $value->name,
                                'VideoNumber' => $value->episode_number,
                                'description' => $value->overview,
                                'image' => $value->backdrop,
                                'adschedule' => $adsArray,
                                'sources' => $videoArray,
                                'tracks' => $subArray,
                                'withCredentials' => true

                            ];
                        } else {
                            $playlist[] = [
                                'title' => $value->name,
                                'VideoNumber' => $value->episode_number,
                                'description' => $value->overview,
                                'image' => $value->backdrop,
                                'adschedule' => $adsArray,
                                'sources' => $videoArray,
                                'tracks' => $subArray,
                                'withCredentials' => true
                            ];
                        }
                    }
                }
            }

            $seasonArray = [];
            if ($getSeasonOne->isEmpty()) {
                $seasonArray = null;
            } else {
                foreach ($getSeasonOne as $key => $value) {
                    array_push($seasonArray, $value);
                }
            }


            return response()->json(
                [
                    'status' => 'success',
                    'data' => [
                        'playlist' => [
                            'playlist' => $playlist
                        ],
                        'series' => $checkAlreadyEpisode,
                        'seasons' => $getAllSeason,
                        'current_season' => $seasonArray,
                        'current_episode' => $current_episode,
                        'geo_status' => true

                    ]
                ]
            )->cookie($cookie_key)
                ->cookie($cookie_policy)
                ->cookie($cookie_signature);
        }
    }


    /**
     * Undocumented function
     *
     * @param Request $request
     * @return void
     */
    public function movieReport(Request $request)
    {
        $request->validate([
            'type' => 'required|numeric',
            'details' => 'nullable|max:100',
            'id' => 'required|uuid',
        ]);

        $add = new Report();
        $add->report_type = $request->type;
        $add->report_details = $request->details;
        $add->report_userid = Auth::user()->id;
        $add->report_movie = $request->id;
        $add->save();

        return response()->json(['status' => 'success']);
    }

    public function seriesReport(Request $request)
    {
        $request->validate([
            'type' => 'required|numeric',
            'details' => 'nullable|max:100',
            'episode_id' => 'required|uuid',
            'series_id' => 'required|uuid',
        ]);

        $add = new Report();
        $add->report_type = $request->type;
        $add->report_details = $request->details;
        $add->report_userid = Auth::user()->id;
        $add->report_episode = $request->episode_id;
        $add->report_series = $request->series_id;
        $add->save();

        return response()->json(['status' => 'success']);
    }

    /**
     * Store recently time episode
     *
     * @return \Illuminate\Http\Response
     */
    public function setRecentlyTimeEpiosde(Request $request)
    {
        $request->validate([
            'current_time' => 'required|numeric',
            'duration_time' => 'required|numeric',
            'episode_id' => 'required|uuid',
            'series_id' => 'required|uuid',
        ]);

        $recently = Recently_watched::join('episodes', 'episodes.id', '=', 'recently_watcheds.episode_id')
            ->where('recently_watcheds.uid', Auth::id())
            ->where('episodes.id', '=', $request->episode_id)
            ->where('episodes.series_id', '=', $request->series_id)
            ->orderBy('episodes.episode_number', 'DESC')
            ->first();

        if (!is_null($recently)) {
            $recently->episode_id = $request->episode_id;
            $recently->current_time = $request->current_time;
            $recently->duration_time = $request->duration_time;
            $recently->save();
            return response()->json(['status' => 'success']);
        } else {
            $store = new Recently_watched();
            $store->episode_id = $request->episode_id;
            $store->series_id = $request->series_id;
            $store->uid = Auth::id();
            $store->current_time = $request->current_time;
            $store->duration_time = $request->duration_time;
            $store->save();
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'failed'], 404);
    }

    /**
     * Store recently time episode
     *
     * @return \Illuminate\Http\Response
     */
    public function setRecentlyTimeMovie(Request $request)
    {
        $request->validate([
            'current_time' => 'required|numeric',
            'duration_time' => 'required|numeric',
            'movie_id' => 'required|uuid',
        ]);

        $recently = Recently_watched::where('uid', Auth::id())->where('movie_id', $request->movie_id)->first();

        if (!is_null($recently)) {
            $recently->current_time = $request->current_time;
            $recently->save();
            return response()->json(['status' => 'success']);
        } else {
            $store = new Recently_watched();
            $store->movie_id = $request->movie_id;
            $store->uid = Auth::id();
            $store->current_time = $request->current_time;
            $store->duration_time = $request->duration_time;
            $store->save();
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'failed'], 404);
    }


    /**
     * Get All Seasons Content
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSeason(Request $request)
    {
        $request->validate([
            'season_number' => 'required|numeric',
            'series_id' => 'required|uuid',
        ]);

        $getSeason = DB::table('episodes')
            ->selectRaw('episodes.*, recently_watcheds.current_time, recently_watcheds.duration_time')
            ->leftJoin('recently_watcheds', function ($join) {
                $join->on('recently_watcheds.episode_id', '=', 'episodes.id')
                    ->where('recently_watcheds.uid', '=', Auth::id());
            })
            ->where('episodes.series_id', $request->input('series_id'))
            ->where('episodes.season_number', $request->input('season_number'))
            ->where('episodes.show', '<>', 0)
            ->orderByRaw('episodes.season_number, episode_number + 0 ASC')
            ->get();



        $seasonArray = [];
        if ($getSeason->isEmpty()) {
            $seasonArray = null;
        } else {
            foreach ($getSeason as $key => $value) {
                if ($value->show == 3) {
                    if (Carbon::now() >= $value->publish_date && Carbon::now() <= $value->expire_date) {
                        if ($value->cloud === 'local') {
                            $value->backdrop = url('/') . '/storage/backdrops/300_' . $value->backdrop;
                        } else {
                            $value->backdrop =  config('aws.cloudfront_public_url')  . '/backdrops/' .  $value->backdrop;
                        }

                        array_push($seasonArray, $value);
                    }
                } else {
                    if ($value->cloud === 'local') {
                        $value->backdrop = url('/') . '/storage/backdrops/300_' . $value->backdrop;
                    } else {
                        $value->backdrop =  config('aws.cloudfront_public_url')  . '/backdrops/' .  $value->backdrop;
                    }

                    array_push($seasonArray, $value);
                }
            }
        }

        return response()->json(
            [
                'status' => 'success',
                'data' => $seasonArray
            ]
        );
    }
}
