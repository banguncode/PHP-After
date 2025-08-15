<?php

namespace PHPAfter;

use PHPFrista\StatusCode;

class Fingerprint
{
    private $token;
    private $username;
    private $password;
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = "https://fp.bpjs-kesehatan.go.id";
    }

    /**
     * Set username and password for VClaim authentication
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function init($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->token = $this->auth();
        return $this;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Authenticate user biometrics
     * @return string|null
     */
    private function auth()
    {
        $url = $this->baseUrl . '/finger-rest/v2/user/login';
        $data = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'Connection: keep-alive'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatus == 200) {
            $response = json_decode($response, true);
            return isset($response['response']['tokenLogin']) ? $response['response']['tokenLogin'] : null;
        }
        return null;
    }

    /**
     * Register biometrics
     * @param string $nokapst
     * @param string $finger base64 encoded right fingerprint, e.g APiBAcgq43NcwEE3CatxcEE...
     * @param string $imgStr base64 encoded image (JPG/PNG) of right fingerprint e.g. /9j/4AAQSkZJRgABAQEAYABgAAD...
     * @param string $finger2 base64 encoded left fingerprint
     * @param string $imgStr2 base64 encoded image (JPG/PNG) of left fingerprint
     * @return array
     */
    public function register($nokapst, $finger, $imgStr, $finger2, $imgStr2)
    {
        if (!$this->token) {
            return ['message' => 'Gagal autentikasi ke server BPJS', 'status' => StatusCode::AUTH_FAILED];
        }

        if (!preg_match('/^\d{13}$/', $nokapst)) {
            return ['message' => 'Nomor peserta tidak valid', 'status' => StatusCode::INVALID_PARTICIPANT];
        }

        if (!$this->validBase64($imgStr) || !$this->validJpeg($imgStr)) {
            return ['message' => 'Format gambar sidik jari tidak valid', 'status' => StatusCode::INVALID_IMAGE];
        }

        if (!$this->validBase64($finger2) || !$this->validJpeg($imgStr2)) {
            return ['message' => 'Format sidik jari tidak valid', 'status' => StatusCode::INVALID_MINUTIAE];
        }

        $url = $this->baseUrl . '/finger-rest/v1/finger/addpeserta';
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'Connection: keep-alive',
            'token: ' . $this->token
        ];
        $data = [
            'nokapst' => $nokapst,
            'finger' => [
                [
                    'finger' => $finger,
                    'index' => 1,
                    'imgStr' => $imgStr
                ],
                [
                    'finger' => $finger2,
                    'index' => 6,
                    'imgStr' => $imgStr2
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatus == 200) {
            return ['message' => 'Sidik jari berhasil didaftarkan', 'status' => StatusCode::OK];
        }
        return ['message' => 'Gagal terhubung ke server BPJS', 'status' => StatusCode::INTERNAL_SERVER_ERROR];
    }

    /**
     * Verify fingerprint
     * @param string $keyword Identity number (13/16 digits)
     * @param string $finger Base64 encoded fingerprint, e.g. AOhPAcgp43NcwEE381mK69...
     * @return array
     */
    public function verify($keyword, $finger)
    {
        if (!$this->token) {
            return ['message' => 'Gagal autentikasi ke server BPJS', 'status' => StatusCode::AUTH_FAILED];
        }

        $jnsFilter = null;
        if (preg_match('/^\d{16}$/', $keyword)) {
            $jnsFilter = 'nik';
        } elseif (preg_match('/^\d{13}$/', $keyword)) {
            $jnsFilter = 'psnoka';
        } else {
            return ['message' => 'Nomor identitas harus 13 atau 16 digit', 'status' => StatusCode::INVALID_ID];
        }

        if (!$this->validBase64($finger)) {
            return ['message' => 'Format sidik jari tidak valid', 'status' => StatusCode::INVALID_MINUTIAE];
        }

        $url = $this->baseUrl . '/finger-rest/v1/finger/verify';
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'Connection: keep-alive',
            'token: ' . $this->token
        ];

        $data = [
            'keyword' => $keyword,
            'jnsFilter' => $jnsFilter,
            'finger' => $finger
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($response, true);

        if ($status == 200) {
            return ['message' => 'Sidik jari berhasil diverifikasi', 'status' => StatusCode::OK];
        }

        if (isset($body['metaData']['code'])) {
            $code = (int)$body['metaData']['code'];
            $message = isset($body['metaData']['message']) ? $body['metaData']['message'] : 'Gagal terhubung ke server BPJS';
            switch ($code) {
                case 1:
                    return ['message' => 'Nomor identitas tidak terdaftar sebagai peserta BPJS', 'status' => StatusCode::NOT_REGISTERED];
                case 2:
                    return ['message' => 'Peserta belum terdaftar sidik jari', 'status' => StatusCode::NOT_ENROLLED];
                case 3:
                    return ['message' => 'Sidik jari tidak dikenal', 'status' => StatusCode::NOT_RECOGNIZED];
                default:
                    return [
                        'status' => StatusCode::INTEGRATION_ERROR,
                        'message' => $message,
                    ];
            }
        }

        return [
            'status' => StatusCode::INTERNAL_SERVER_ERROR,
            'message' => 'Internal Server Error',
        ];
    }

    /**
     * Reset (delete) fingerprint data for a participant
     * @param string $nokapst Participant number
     * @param string $reason Reason for reset (01 = Enrollment (Pembacaan) Sidik Jari, 02 = Fisik Sidik Jari Rusak/Cacat)
     * @return array
     */
    public function reset($nokapst, $reason = "02")
    {
        if (!$this->token) {
            return ['message' => 'Gagal autentikasi ke server BPJS', 'status' => StatusCode::AUTH_FAILED];
        }

        if (!preg_match('/^\d{13}$/', $nokapst)) {
            return ['message' => 'Nomor peserta tidak valid', 'status' => StatusCode::INVALID_PARTICIPANT];
        }

        $url = $this->baseUrl . "/finger-rest/v1/finger/delpeserta/{$nokapst}/{$reason}";
        $headers = [
            'Accept: application/json',
            'token: ' . $this->token
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($response, true);

        if ($httpStatus == 200) {
            return ['message' => 'Sidik jari berhasil dihapus', 'status' => StatusCode::OK];
        }

        $message = isset($body['metaData']['message']) ? $body['metaData']['message'] : 'Gagal menghapus sidik jari';
        return [
            'message' => $message,
            'status' => $httpStatus
        ];
    }

    private function validBase64($base64)
    {
        return base64_decode($base64, true) !== false;
    }

    private function validJpeg($base64)
    {
        if (strpos($base64, 'data:image/') === 0) {
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        }

        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            return false;
        }

        // Check JPEG
        if (substr($decoded, 0, 2) === "\xFF\xD8" && substr($decoded, -2) === "\xFF\xD9") {
            return true;
        }
        // Check PNG
        if (substr($decoded, 0, 8) === "\x89PNG\x0D\x0A\x1A\x0A") {
            return true;
        }

        return false;
    }
}
