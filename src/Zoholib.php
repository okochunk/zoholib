<?php

namespace Yls\Zoholib;

use App\Models\Ylsgt\WpformTeacher;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ZohoService
{
    function generateRefreshToken($code)
    {
        $post_field                  = [];
        $post_field['client_id']     = Config::get('services.zoho.client_id');
        $post_field['client_secret'] = Config::get('services.zoho.client_secret');
        $post_field['redirect_uri']  = Config::get('services.zoho.redirect_uri');
        $post_field['code']          = $code;
        $post_field['grant_type']    = 'authorization_code';

        $ch = curl_init();
        $ch_options = array(
            CURLOPT_URL => "https://accounts.zoho.com/oauth/v2/token",
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_field,
            CURLOPT_HEADER => 0,
            CURLOPT_VERBOSE => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0
        );

        curl_setopt_array($ch, $ch_options);
        $response = curl_exec($ch);
        $response = json_decode($response);

        curl_close($ch);

        if (isset($response->refresh_token)) {
            Cache::put('refresh_token', $response->refresh_token);
        }

        return json_decode($response, true);
    }

    function generateAccessToken($existing_refresh_token)
    {
        $post_field                  = [];
        $post_field['client_id']     = Config::get('services.zoho.client_id');
        $post_field['client_secret'] = Config::get('services.zoho.client_secret');
        $post_field['redirect_uri']  = Config::get('services.zoho.redirect_uri');
        $post_field['refresh_token'] = !empty($existing_refresh_token) ? $existing_refresh_token : Cache::get('refresh_token');
        $post_field['grant_type']    = 'refresh_token';

        $ch =curl_init();
        $ch_options = array(
            CURLOPT_URL => "https://accounts.zoho.com/oauth/v2/token",
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_field,
            CURLOPT_HEADER => 0,
            CURLOPT_VERBOSE => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0
        );

        curl_setopt_array($ch, $ch_options);
        $response = curl_exec($ch);
        $response = json_decode($response);

        curl_close($ch);

        Cache::put('zoho_access_token', $response->access_token);

        print($response->access_token. PHP_EOL);
    }

    function listDepartments()
    {
        $oauth_token = Cache::get('zoho_access_token');

        $headers=[
            "Authorization:Zoho-oauthtoken " . $oauth_token,
            "contentType: application/json; charset=utf-8",
        ];

        $url="https://desk.zoho.com/api/v1/departments?limit=100";

        $ch= curl_init($url);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);

        $response= curl_exec($ch);

        curl_close($ch);

        return json_decode($response, true);
    }

    function listAgents()
    {
        $oauth_token = Cache::get('zoho_access_token');

        $headers=[
            "Authorization:Zoho-oauthtoken " . $oauth_token,
            "contentType: application/json; charset=utf-8",
        ];

        $url="https://desk.zoho.com/api/v1/agents?limit=200";

        $ch= curl_init($url);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);

        $response= curl_exec($ch);

        curl_close($ch);

        return json_decode($response, true);
    }

    function listContacts()
    {
        $oauth_token = Cache::get('zoho_access_token');

        $headers=[
            "Authorization:Zoho-oauthtoken " . $oauth_token,
            "contentType: application/json; charset=utf-8",
        ];

        $url="https://desk.zoho.com/api/v1/contacts?limit=100";

        $ch= curl_init($url);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    function createTicket($data)
    {
        $departmentId = Config::get('services.zoho.default_department_id');
        if (App::environment('production')) {
            $departmentId = $data['department'];
        }

        $oauth_token = Cache::get('zoho_access_token');

        $headers = [
            "Authorization:Zoho-oauthtoken " . $oauth_token,
            "contentType: application/json; charset=utf-8",
        ];

        $ticket_data = [
            'subject'      => $data['subject'],
            'departmentId' => $departmentId, // get from db for development mode
            'contact'      => ['lastName' => $data['name'], 'email' => $data['email']],
            'phone'        => $data['phone'],
            'email'        => $data['email'],
            'description'  => $data['description'],
            'assigneeId'   => $data['assignee_id']
        ];

        $url="https://desk.zoho.com/api/v1/tickets";

        $ticket_data = (gettype($ticket_data)==="array") ? json_encode($ticket_data) : $ticket_data;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ticket_data);

        $response = curl_exec($ch);
        $result['info']     = curl_getinfo($ch);
        $result['response'] = json_decode($response);
        curl_close($ch);

        return $result;
    }

    function createTicketComment($data)
    {
        $oauth_token = Cache::get('zoho_access_token');

        $headers = [
            "Authorization:Zoho-oauthtoken " . $oauth_token,
            "contentType: application/json; charset=utf-8",
        ];

        $ticket_comment_data = [
            'isPublic' => true,
            'contentType' => 'plainText',
            'content' => $data['content']
        ];

        $url="https://desk.zoho.com/api/v1/tickets/".$data['ticket_id']."/comments";

        $ticket_comment_data = (gettype($ticket_comment_data)==="array") ? json_encode($ticket_comment_data) : $ticket_comment_data;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ticket_comment_data);

        $response = curl_exec($ch);
        $result['info']     = curl_getinfo($ch);
        $result['response'] = json_decode($response);
        curl_close($ch);

        return $result;

    }

}
