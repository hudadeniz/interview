<?php

class TurkpinApi
{
    private string $apiUrl;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->apiUrl = $_ENV['TURKPIN_API_URL'] ?? '';
        $this->username = $_ENV['TURKPIN_API_USERNAME'] ?? '';
        $this->password = $_ENV['TURKPIN_API_PASSWORD'] ?? '';
    }

    private function request(array $params): SimpleXMLElement
    {
        $params['username'] = $this->username;
        $params['password'] = $this->password;

        $xml = '<APIRequest><params>';

        foreach ($params as $key => $value) {
            $xml .= '<' . $key . '>' .
                htmlspecialchars((string) $value, ENT_XML1) .
                '</' . $key . '>';
        }

        $xml .= '</params></APIRequest>';

        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'DATA' => $xml
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);

        throw new RuntimeException('API bağlantı hatası: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new RuntimeException('API HTTP hatası: ' . $httpCode);
        }

        $result = simplexml_load_string($response);

        if ($result === false) {
            throw new RuntimeException('API geçersiz XML döndürdü.');
        }

        $errorCode = (string) ($result->params->HATA_NO ?? '');

        if ($errorCode !== '' && $errorCode !== '000') {
            $errorMessage = (string) (
                $result->params->HATA_ACIKLAMA ?? 'Bilinmeyen API hatası'
            );

            throw new RuntimeException(
                "Turkpin API Hatası [$errorCode]: $errorMessage"
            );
        }

        return $result;
    }

    public function getGames(): SimpleXMLElement
    {
        return $this->request([
            'cmd' => 'epinOyunListesi'
        ]);
    }

    public function getProducts(
        string $gameCode,
        ?string $productCode = null
    ): SimpleXMLElement {
        $params = [
            'cmd' => 'epinUrunleri',
            'oyunKodu' => $gameCode,
        ];

        if ($productCode !== null) {
            $params['urunKodu'] = $productCode;
        }

        return $this->request($params);
    }

    public function createOrder(
        string $gameCode,
        string $productCode,
        int $quantity,
        ?string $character = null
    ): SimpleXMLElement {
        $params = [
            'cmd' => 'epinSiparisYarat',
            'oyunKodu' => $gameCode,
            'urunKodu' => $productCode,
            'adet' => $quantity,
        ];

        if ($character !== null) {
            $params['character'] = $character;
        }

        return $this->request($params);
    }
}