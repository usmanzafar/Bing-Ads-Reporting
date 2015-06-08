<?php

namespace Clicksco\Bingads\Bing;

use Clicksco\Bingads\Exceptions\MissingRequiredSettingsException;
use BingAds\Proxy\ClientProxy;

class BingUser
{

    public $access_token;
    public $settings;
    public $proxy;
    public $wsdl;
    protected $token_url;

    public function __construct($settings)
    {
        $this->getUser($settings);
    }

    /**
     * Main function responsible for generating access token as well as proxy object used by all Bing Services
     *
     * @param $settings
     * @throws MissingRequiredSettingsException
     * @throws \Exception
     */
    public function getUser($settings)
    {
        $this->settings = $this->verifySettings($settings);
        $this->prepareURL();
        $this->settings = $settings;
        $this->access_token = $this->getAccessToken($settings);
        $this->verifyWSDL($this->wsdl);
        $this->proxy = $this->getProxy();
       print('Access Token generated successfully'.PHP_EOL);
    }

    /**
     * Prepares the url in order to request the access token
     */
    protected function prepareURL()
    {
        $url = 'https://login.live.com/oauth20_token.srf?';
        $url .= 'client_id=' . $this->settings['client_id'];
        $url .= '&client_secret=' . $this->settings['client_secret'];
        $url .= '&grant_type=refresh_token';
        $url .= '&refresh_token=' . $this->settings['refresh_token'];
        $this->token_url = $url;
    }

    /**
     * Ensures that settings comply with what required to access the proxy object and access token
     *
     * @param $settings
     * @return mixed
     * @throws MissingRequiredSettingsException
     */
    protected function verifySettings($settings)
    {
        if (!is_array($settings)) {
            throw new MissingRequiredSettingsException('Invalid Settings, expects array as setting parameters');
        }

        $required = ['client_id', 'client_secret', 'developer_token', 'account_id', 'refresh_token', 'customer_id'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $settings)) {
                throw new MissingRequiredSettingsException('Required setting missing : ' . $key);
            }
        }

        return $settings;
    }

    /**
     * @param $wsdl_URL
     * @return mixed
     * @throws MissingRequiredSettingsException
     */
    protected function verifyWSDL($wsdl_URL)
    {
        if (!filter_var($wsdl_URL, FILTER_VALIDATE_URL) === false) {
            return $wsdl_URL;
        } else {
            throw new MissingRequiredSettingsException('WSDL Url format not valid or empty');
        }
    }

    /*
     * Uses the refresh token provided in order to make a new Access token
     */
    protected function getAccessToken()
    {

        $response_file = file_get_contents($this->token_url);
        $response = json_decode($response_file);
        if (!is_null($response->access_token)) {
            return $response->access_token;
        } else {
            throw new \Exception('Error Occurred while requesting access tokens,try to request refresh token again');
        }

    }

    /*
     * Retrieves the proxy object based on the WSDL provided
     */
    protected function getProxy()
    {
        return ClientProxy::ConstructWithAccountAndCustomerId(
            $this->wsdl,
            null,
            null,
            $this->settings['developer_token'],
            $this->settings['account_id'],
            $this->settings['customer_id'],
            $this->access_token
        );
    }
}
