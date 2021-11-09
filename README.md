# zoholib
laravel simple zoho library 

# installation 
composer create-project yls/zoholib

Library to use part of https://desk.zoho.com/DeskAPIDocument list of feature
* Authentication(OAuth) Generate Refresh Token
* Generate Access Token
* Get List Departments
* Get List Agents
* Get List Contacts
* Create Ticket
* create Ticket Comment

# usage
add on .env
- ZOHO_CLIENT_ID="1000.GPWZ1R45EK2K8X8MY2ZVBOKR5DBQ3X"
- ZOHO_CLIENT_SECRET="e137da3dff3a880563b9bd0b3bb122543426211b7c"
- ZOHO_REFRESH_TOKEN="1000.1841927626424bd4b8bc716f97d94aa8.377acdba4a770fb653320fb8c042fd4c"



* on your controller
```php
$data['subject']     = Config::get('services.zoho.subject.leads_cm');
$data['id']          = $leads->mt_form_id;
$data['phone']       = $leads->mobile;
$data['email']       = $leads->email;
$data['name']        = $leads->name;
$data['department']  = Config::get('services.zoho.default_department_id');
$data['assignee_id'] = Config::get('services.zoho.assignee_id');

$data['description'] = 'Name: ' . $leads->name . '<br>';

$zoho = new ZohoService();
$ticket = $zoho->createTicket($data);
```
