<?php

namespace App\Libraries;

use Config\Services;

class VirtualMinShell
{
	static $output;

	protected function execute($cmd, $title = '')
	{
		if (/*ENVIRONMENT === 'production'*/true || $title === NULL) {
			set_time_limit(300);
			$username = Services::request()->config->sudoWebminUser;
			$password = Services::request()->config->sudoWebminPass;
			if ($title !== NULL)
				VirtualMinShell::$output .= 'HOSTING: ' . $title . ' (' . $cmd . ')' . "\n";
			$ch = curl_init($cmd);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			$response = curl_exec($ch);
			if ($title !== NULL)
				VirtualMinShell::$output .= $response . "\n";
			curl_close($ch);
			return $response;
		} else {
			VirtualMinShell::$output .= 'HOSTING: ' . $title . "\n";
			VirtualMinShell::$output .= $cmd . "\n";
		}
	}
	protected function wrapWget($params, $server)
	{
		$port = Services::request()->config->sudoWebminPort;
		return "https://$server.domcloud.id:$port/virtual-server/remote.cgi?$params";
	}
	public function createHost($username, $password, $email, $domain, $server, $plan)
	{
		$epassword = urlencode($password);
		$cmd = "program=create-domain&user=$username&pass=$epassword" .
			"&email=$email&domain=$domain&plan=$plan&limits-from-plan=" .
			"&dir=&webmin=&virtualmin-nginx=&virtualmin-nginx-ssl=&unix=";
		$this->execute($this->wrapWget($cmd, $server), " Create Host for $domain ");
	}
	public function upgradeHost($domain, $server, $newplan)
	{
		$cmd = "program=modify-domain&domain=$domain&apply-plan=$newplan";
		$this->execute($this->wrapWget($cmd, $server));
	}
	public function renameHost($domain, $server, $newusername)
	{
		$cmd = "program=modify-domain&domain=$domain&user=$newusername";
		$this->execute($this->wrapWget($cmd, $server), " Rename hosting $domain ");
	}
	public function cnameHost($domain, $server, $newdomain)
	{
		$cmd = "program=modify-domain&domain=$domain&newdomain=$newdomain";
		$this->execute($this->wrapWget($cmd, $server), " Change domain for $domain ");
	}
	public function resetHost($domain, $server, $newpw)
	{
		$cmd = "program=modify-domain&domain=$domain&pass=$newpw";
		$this->execute($this->wrapWget($cmd, $server), "Reset Host Password");
	}
	public function enableHost($domain, $server)
	{
		$cmd = "program=enable-domain&domain=$domain";
		$this->execute($this->wrapWget($cmd, $server));
	}
	public function disableHost($domain, $server, $why)
	{
		$cmd = "program=disable-domain&domain=$domain&why=" . urlencode($why);
		$this->execute($this->wrapWget($cmd, $server));
	}
	public function deleteHost($domain, $server)
	{
		$cmd = "program=delete-domain&domain=$domain";
		$this->execute($this->wrapWget($cmd, $server), " Delete Host for $domain ");
	}
	public function requestLetsEncrypt($domain, $server)
	{
		$cmd = "program=generate-letsencrypt-cert&domain=$domain&renew=2";
		$this->execute($this->wrapWget($cmd, $server), " Let's Encrypt for $domain ");
	}
	public function enableFeature($domain, $server, $features)
	{
		$cmd = "program=enable-feature&domain=$domain" . implode('', array_map(function ($x) {
			return "&$x=";
		}, $features));
		$this->execute($this->wrapWget($cmd, $server), " Enable Features for $domain ");
	}
	public function adjustBandwidthHost($bw_mb, $domain, $server)
	{
		$bw_bytes = floor($bw_mb) * 1024 * 1024;
		$cmd = "program=modify-domain&domain=$domain&bw=$bw_bytes";
		$this->execute($this->wrapWget($cmd, $server), " Adjust Bandwidth $domain to $bw_bytes bytes ");
	}
	public function createDatabase($name, $type, $domain, $server)
	{
		$name = urlencode($name);
		$cmd = "program=create-database&domain=$domain&name=$name&type=$type";
		$this->execute($this->wrapWget($cmd, $server), " Create database $domain named $name ");
	}
	public function modifyWebHome($home, $domain, $server)
	{
		$home = urlencode($home);
		$cmd = "program=modify-web&domain=$domain&document-dir=$home";
		$this->execute($this->wrapWget($cmd, $server), " Set home $domain named $home ");
	}
	public function listDomainsInfo($server)
	{
		$cmd = "program=list-domains&multiline=&toplevel=";
		$data = $this->execute($this->wrapWget($cmd, $server), NULL);

		$data = explode("\n", $data);
		$result = [];
		$neskey = null;
		$nesval = [];
		foreach ($data as $line) {
			$line = rtrim($line);
			if (strlen($line) >= 4 && $line[0] === ' ') {
				$line = explode(':', ltrim($line), 2);
				$nesval[$line[0]] = ltrim($line[1]);
			} else if (strlen($line) >= 0) {
				if ($neskey) {
					$result[$neskey] = $nesval;
					$nesval = [];
				}
				$neskey = $line;
			} else {
				$result[$neskey] = $nesval;
				break;
			}
		}
		return $result;
	}
	public function listBandwidthInfo($server)
	{
		$cmd = "program=list-bandwidth&all-domains=";
		$data = $this->execute($this->wrapWget($cmd, $server), NULL);

		$data = explode("\n", $data);
		$result = [];
		$neskey = null;
		$nesval = [];
		foreach ($data as $line) {
			$line = rtrim($line);
			if (strlen($line) >= 4 && $line[0] === ' ') {
				$line = explode(':', ltrim($line), 3);
				$nesval[$line[0]] = ltrim($line[2]);
			} else if (strlen($line) >= 0) {
				if ($neskey) {
					$result[$neskey] = $nesval;
					$nesval = [];
				}
				$neskey = rtrim($line, ":\r");
			} else {
				$result[$neskey] = $nesval;
				break;
			}
		}
		return $result;
	}
	public function listSystemInfo($server)
	{
		$cmd = "program=info";
		$data = $this->execute($this->wrapWget($cmd, $server), NULL);
		$data = str_replace('*', '-', $data);
		return $data;
	}
}
