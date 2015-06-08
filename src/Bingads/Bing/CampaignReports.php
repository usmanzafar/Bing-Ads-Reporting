<?php namespace Clicksco\Bingads\Bing;

use BingAds\Reporting\AccountThroughCampaignReportScope;
use BingAds\Reporting\CampaignPerformanceReportColumn;
use BingAds\Reporting\CampaignPerformanceReportRequest;
use BingAds\Reporting\ReportFormat;
use BingAds\Reporting\ReportAggregation;
use BingAds\Reporting\CampaignReportScope;
use BingAds\Reporting\ReportTime;
use BingAds\Reporting\ReportTimePeriod;
use BingAds\Reporting\Date;
use BingAds\Reporting\ReportRequestStatusType;


class CampaignReports extends ReportBase
{

    public $download_path;

    /**
     * Main function responsible for generating the reports for Campaigns
     *
     * @param $campaign_id
     * @param null $request_date
     * @param bool $return_file_contents
     * @throws \Clicksco\Bingads\Exceptions\DownloadFileException
     * @throws \Clicksco\Bingads\Exceptions\ExtractFileException
     * @throws \Clicksco\Bingads\Exceptions\FileReadException
     */
    public function generateReports($campaign_id, $request_date = null, $return_file_contents = true)
    {

        try {
            $report = new CampaignPerformanceReportRequest();

            $report->Format = ReportFormat::Csv;
            $report->ReportName = 'Campaign Report for ' . $campaign_id;
            $report->Aggregation = ReportAggregation::Summary;

            $report->Scope = new AccountThroughCampaignReportScope();
            $report->Scope->Campaigns = array();

            $campaignReportScope = new CampaignReportScope();
            $campaignReportScope->CampaignId = $campaign_id;
            $campaignReportScope->AccountId = $this->settings['account_id'];
            $report->Scope->Campaigns[] = $campaignReportScope;

            $report->Time = new ReportTime();

            if (is_null($request_date)) {
                $report->Time->PredefinedTime = ReportTimePeriod::Today;
            } else {
                $report->Time->CustomDateRangeStart = new Date();
                $report->Time->CustomDateRangeStart->Month = $request_date['start_month'];
                $report->Time->CustomDateRangeStart->Day = $request_date['start_day'];
                $report->Time->CustomDateRangeStart->Year = $request_date['start_year'];
                $report->Time->CustomDateRangeEnd = new Date();
                $report->Time->CustomDateRangeEnd->Month = $request_date['end_month'];
                $report->Time->CustomDateRangeEnd->Day = $request_date['end_day'];
                $report->Time->CustomDateRangeEnd->Year = $request_date['end_year'];
            }

            //Setting the reports Column
            $report->Columns = array(
                CampaignPerformanceReportColumn::AccountId,
                CampaignPerformanceReportColumn::CampaignId,
                CampaignPerformanceReportColumn::CampaignName,
                CampaignPerformanceReportColumn::Impressions,
                CampaignPerformanceReportColumn::CurrencyCode,
                CampaignPerformanceReportColumn::Spend,
                CampaignPerformanceReportColumn::Clicks,
                CampaignPerformanceReportColumn::Conversions,
                CampaignPerformanceReportColumn::AverageCpc,
            );

            //Setting up the report

            $encodedReport = new \SoapVar($report, SOAP_ENC_OBJECT, 'CampaignPerformanceReportRequest', $this->proxy->GetNamespace());

            //Step 1: Request ID
            $reportRequestId = $this->submitGenerateReport($encodedReport);
            printf("Report Request ID: %s\n\n", $reportRequestId);

            //Step 2: Poll the report
            $waitTime = 30 * 1;
            $reportRequestStatus = null;

            for ($i = 0; $i < 10; $i++) {
                sleep($waitTime);
                print('Slept for :' . $waitTime . ' - iteration no: ' . $i.PHP_EOL);

                $reportRequestStatus = $this->pollGenerateReport($reportRequestId);
                if ($reportRequestStatus->Status == ReportRequestStatusType::Success ||
                    $reportRequestStatus->Status == ReportRequestStatusType::Error
                ) {
                    print('Status:' . $reportRequestStatus->Status);
                    break;
                }
            }

            //Step 3: Download the report
            if ($reportRequestStatus != null) {
                if ($reportRequestStatus->Status == ReportRequestStatusType::Success) {
                    $reportDownloadUrl = $reportRequestStatus->ReportDownloadUrl;
                    $this->downloadFile($reportDownloadUrl, $this->download_path);//download path should set in the app
                    //Step 3.1: Extract the file
                    $report_file_path = $this->extractFile($this->download_path);

                    //Step 3.2: Read the content
                    if ($return_file_contents) {
                        $file_content = $this->readFileContents($report_file_path);
                        return $file_content;
                    } else {
                        return $report_file_path;
                    }

                } else {
                    if ($reportRequestStatus->Status == ReportRequestStatusType::Error) {
                        print('The request failed. Try requesting the report'.PHP_EOL);
                    } else {
                        // Pending
                        print('The request is taking longer than expected' . $reportRequestId.PHP_EOL);
                    }
                }
            }
        } catch (SoapFault $ex) {
            print('SoapFault Exception: ' . $ex->getMessage());
        } catch (Exception $e) {
            if ($e->getPrevious()) {
                ; // Ignore fault exceptions that we already caught.
            } else {
                print('Normal Exception: ' . PHP_EOL);
                print('Normal Exception: ' . $e->getMessage().PHP_EOL);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getDownloadPath()
    {
        return $this->download_path;
    }

    /**
     * @param mixed $download_path
     */
    public function setDownloadPath($download_path)
    {
        $this->download_path = $download_path;
    }
}
