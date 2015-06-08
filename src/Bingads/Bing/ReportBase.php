<?php

namespace Clicksco\Bingads\Bing;

use Clicksco\Bingads\Bing\BingUser;
use Clicksco\Bingads\Exceptions\DownloadFileException;
use Clicksco\Bingads\Exceptions\ExtractFileException;
use Clicksco\Bingads\Exceptions\FileReadException;
use League\Csv\Reader;
use ZipArchive;
use SplFileObject;
use BingAds\Reporting\SubmitGenerateReportRequest;
use BingAds\Reporting\PollGenerateReportRequest;


class ReportBase extends BingUser
{


    public function __construct($settings)
    {
        $this->wsdl = 'https://api.bingads.microsoft.com/Api/Advertiser/Reporting/V9/ReportingService.svc?singleWsdl';
        parent::__construct($settings);
    }

    /**
     * Request the report. Use the ID that the request returns to check for the completion of the report.
     *
     * @param $report
     * @return mixed
     */

    protected function submitGenerateReport($report)
    {
        $request = new SubmitGenerateReportRequest();
        $request->ReportRequest = $report;

        return $this->proxy->GetService()->SubmitGenerateReport($request)->ReportRequestId;
    }

    /*
     * Check the status of the report request. The guidance of how often to poll for status is from every five to 15 minutes depending on the amount
     * of data being requested. For smaller reports, you can poll every couple of minutes. You should stop polling and try again later if the
     * request is taking longer than an hour.
     * @param $reportRequestID
     */
    protected function pollGenerateReport($reportRequestId)
    {
        $request = new PollGenerateReportRequest();
        $request->ReportRequestId = $reportRequestId;

        return $this->proxy->GetService()->PollGenerateReport($request)->ReportRequestStatus;
    }

    /**
     * Using the URL that the PollGenerateReport operation returned,send an HTTP request to get the report and write it to the specified ZIP file
     *
     * @param $reportDownloadUrl
     * @param $downloadPath
     * @throws DownloadFileException
     */
    protected function downloadFile($reportDownloadUrl, $downloadPath)
    {

        try {
            $ch = curl_init($reportDownloadUrl);
            $fp = fopen($downloadPath, 'w');
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //something wrong with bingads Server SSL certificate
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //something wrong with bingads Server SSL certificate
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_exec($ch);
            //$retcode = curl_getinfo($ch);used for debugging
            curl_close($ch);
            fclose($fp);
            print('File download successfully'.PHP_EOL);

        } catch (\Exception $ex) {
            throw new DownloadFileException('Could not download file');
        }

    }

    /**
     * Extracts the zip file as per the path provided. The access is public so can be used via the object of
     * any class derived from ReportBase
     *
     * @param $download_path
     * @return string
     * @throws ExtractFileException
     */
    public function extractFile($download_path)
    {
        $extract_file_path = dirname($download_path);
        $zip = new ZipArchive();
        $res = $zip->open($download_path);
        $extractedFileName = $zip->getNameIndex(0);

        if (count($extractedFileName) > 0 and ($res === true)) {
            $zip->extractTo($extract_file_path);
            print('Bing report extracted with name:' . $extractedFileName.PHP_EOL);
            $file_path = $extract_file_path . '/' . $extractedFileName;
            return $file_path;
        } else {
            throw new ExtractFileException('Could not to extract the file');
        }

    }

    /**
     * Reads the file content and throws back the array contents of the file
     * Have public access so can be used objects as a utility
     *
     * @param $file_path
     * @param bool $do_parse
     * @return array
     * @throws FileReadException
     */
    public function readFileContents($file_path, $do_parse = true)
    {
        if (!$do_parse) {
            return $file_path;
        }

        if (!ini_get("auto_detect_line_endings")) {
            ini_set("auto_detect_line_endings", '1');
        }

        try {
            $reader = Reader::createFromPath($file_path);
            $reader->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);
            return $reader->fetchAll();
        } catch (\Exception $ex) {
            throw new FileReadException('Could not read the csv file.');
        }

    }

    /**
     * Clears the directory of all the zip files or all files based on the parameter
     * Have public access so can be used objects as a utility
     *
     * @param $download_folder
     * @param bool $ziponly
     */
    public function truncateReportFolder($download_folder, $ziponly = true)
    {
        if ($ziponly) {
            $files = glob($download_folder . '/*.zip');
        } else {
            $files = glob($download_folder . '/*');
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            } // delete file
        }
        print('Files Removed from '.$download_folder.PHP_EOL);
    }
}
