# Bing-Ads-Reporting
Laravel package which can be used to download, extract the reports of campaigns
##ToDo
Add the support for adgroups and Keywords, PR would be awesome to help this go further

---
Clicksco Bing API Package

This is a Clicksco wrapper around the Bings Ads API.

Currently, only reporting service is supported.You can use this package to:

    - Create the Campaign Summary Reports

## Install
```
composer install clicksco/bingads
```
## Bing Create Campaign Setup
To call all of the functions in this package you will need to make sure that settings array should have all the relevant information required to authenticate and do the desired process.

```
$settings  = array(
            'client_id' => getenv('BING_CLIENT_ID'),
            'client_secret' => getenv('BING_CLIENT_SECRET') ,
            'developer_token' => getenv('BING_DEVELOPER_TOKEN'),
            'refresh_token' => getenv('BING_REFRESH_TOKEN'),
            'account_id' => 'xxxx',
            'customer_id' => getenv('BING_CUSTOMER_ID'),
        );
 $report = $campaigns_report->generateReports($campaign_id,$request_date);
```
##Helpers for report
You can use the below functions on the report object
```
$campaigns_report->truncateReportFolder(dirname($download_path));
$campaigns_report->readFileContents('filepath.csv');
$campaigns_report->extractFile('zip file path');
```

