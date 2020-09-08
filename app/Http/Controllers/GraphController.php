<?php

namespace App\Http\Controllers;

use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class GraphController extends Controller
{
    private $api;
    public function __construct(Facebook $fb)
    {
        $this->middleware(function ($request, $next) use ($fb) {
            $fb->setDefaultAccessToken(Auth::user()->token);
            $this->api = $fb;
            return $next($request);
        });
    }
    public function makeApiCall( $endpoint, $type, $params ) {
        $ch = curl_init();

        if ( 'POST' == $type ) {
            curl_setopt( $ch, CURLOPT_URL, $endpoint );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
            curl_setopt( $ch, CURLOPT_POST, 1 );
        } elseif ( 'GET' == $type ) {
            curl_setopt( $ch, CURLOPT_URL, $endpoint . '?' . http_build_query( $params ) );
        }

        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $response = curl_exec( $ch );
        curl_close( $ch );

        return json_decode( $response, true );
    }
    public function retrieveUserProfile(){
        try {
//            $params = "first_name,last_name,accounts{instagram_business_account,access_token,name,category,tasks}";
            $params = "accounts{access_token,name,category,tasks,instagram_business_account{follows_count,followers_count,biography,media_count,name,username,media{media_type,comments_count,like_count,caption,media_url,children{media_url},permalink}}}";
            $paramsig = "instagram_business_account";
            $paramsiginfo = "follows_count,followers_count,biography,media_count,name,username,media{media_type,comments_count,like_count,caption,media_url,children{media_url},permalink}";
            $period = "lifetime";
            $metric = "audience_gender_age";

            $user = $this->api->get('/me?fields='.$params)->getGraphUser();
//            $instagram = $this->api->get('/'.$user['accounts']['0']['id'].'?fields='.$paramsig)->getGraphUser();
//            $intagraminfo = $this->api->get('/'.$instagram['instagram_business_account']['id'].'?fields='.$paramsiginfo)->getGraphUser();
//            $folls_audience = $this->api->get('/17841400652158726/insights?period='.$period.'&metric='.$metric);
//            $folls_audience = get_object_vars($folls_audience);
//            $userInsightsEndpoingFormat = 'https://graph.facebook.com/v8.0/' .$instagram['instagram_business_account']['id'].'/insights?metric=follower_count,impressions,profile_views,reach&period=day&access_token='.$user['accounts']['0']['access_token'];
//            // get user insights
//            $userInsightsEndpoint = 'https://graph.facebook.com/v8.0/' . $instagram['instagram_business_account']['id'] . '/insights';
//            $userInsightParams = array(
//                'metric' => 'follower_count,impressions,profile_views,reach',
//                'period' => 'day',
//                'access_token' => $user['accounts']['0']['access_token']
//            );
//            $userInsights = $this->makeApiCall( $userInsightsEndpoint, 'GET', $userInsightParams );
            $array = json_decode($user['accounts']);
            $filtered = array();
            for($i=0;$i<count($array);$i++)
            {
                if(isset($array[$i]->instagram_business_account)){
                    $filtered[] = $array[$i];
                }
            }
            return $filtered;

//            return response()->json([
//                'facebook' =>[
//                    'first_name' => $user['first_name'],
//                    'last_name' => $user['last_name'],
//                    'access_token' => $user['accounts']['0']['access_token'],
//                    'page_name' => json_decode($user['accounts']),
//                    'page_category' => $user['accounts']['0']['category'],
//                    'id_page' => $user['accounts']['0']['id'],
//                ],
//                'instagram' => [
//                    'user_id'=>$instagram['instagram_business_account']['id'],
//                    'username'=>$intagraminfo['username'],
//                    'name'=>$intagraminfo['name'],
//                    'total_posts'=>$intagraminfo['media_count'],
//                    'following'=>$intagraminfo['follows_count'],
//                    'followers'=>$intagraminfo['followers_count'],
//                    'insights'=> $userInsights['data'],
//                    'biography'=>$intagraminfo['biography'],
//                    'media'=>json_decode($intagraminfo['media'])
//                ],
//            ], 200);
//            foreach ( $userInsights['data'] as $insight ) :
//                echo $insight['title'];
//                foreach ( $insight['values'] as $value ) :
//                    echo $value['value'].' '.$value['end_time'];
//                    endforeach;
//            endforeach;
//            dd($userInsights['data']);
        } catch (FacebookSDKException $e) {
            // dd($e);
        }

    }

    public function getPageAccessToken($page_id){
        try {
            // Get the \Facebook\GraphNodes\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.
            $response = $this->api->get('/me/accounts', Auth::user()->token);
        } catch(FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        try {
            $pages = $response->getGraphEdge()->asArray();
            foreach ($pages as $key) {
                if ($key['id'] == $page_id) {
                    return $key['access_token'];
                }
            }
        } catch (FacebookSDKException $e) {
            dd($e); // handle exception
        }
    }
}
