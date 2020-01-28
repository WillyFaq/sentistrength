# PHP sentistrength

[![Build Status](https://travis-ci.org/WillyFaq/sentistrength.svg?branch=master)](https://travis-ci.org/WillyFaq/sentistrength)
[![GitHub](https://img.shields.io/github/license/WillyFaq/sentistrength)]()
[![Packagist Version](https://img.shields.io/packagist/v/wfphpnlp/sentistrength)](https://packagist.org/packages/wfphpnlp/sentistrength)

Library PHP untuk klasifikasi teks menjadi klasifikasi positif, negatif dan netral pada Bahasa Indonesia menggunakan metode SentiSrength.

## Cara Install
### Via Composer
```bash
composer require wfphpnlp/sentistrength
```
Jika Anda masih belum memahami bagaimana cara menggunakan Composer, silahkan baca [Getting Started with Composer](https://getcomposer.org/doc/00-intro.md).
### Clone GitHub
```bash
git clone https://github.com/WillyFaq/Penilaian_Guru_Rating_Scale.git
```
## Cara Penggunaan
jika menggunakan composer inisiasikan projek anda dengan `vendor/autoload.php`
```php
require_once __DIR__ . '/vendor/autoload.php';
use wfphpnlp/sentistrength;
```
configurasikan penggunaan kamus tambahan, jika tidak dikonfigurasi maka semua konfigurasi kamus akan digunakan.
```php
$config = array(
    			'negation_conf' => true,
    			'booster_conf' => true,
    			'ungkapan_conf' => true,
    			'consecutive_conf' => true,
    			'repeated_conf' => true,
    			'emoticon_conf' => true,
    			'question_conf' => true,
    			'exclamation_conf' => true,
    			'punctuation_conf' => true,
			);
```
Berikut contoh lengkap penggunaan.
```php
<?php
// include composer autoloader
require_once __DIR__ . '/vendor/autoload.php';
use wfphpnlp/sentistrength;

$config = array(
			'negation_conf' => true,
			'booster_conf' => true,
			'ungkapan_conf' => true,
			'consecutive_conf' => true,
			'repeated_conf' => true,
			'emoticon_conf' => true,
			'question_conf' => true,
			'exclamation_conf' => true,
			'punctuation_conf' => true,
			);
			
// create sentistrength
$senti = new Sentistrength($config);

// hitung nilai sentistrength
$hasil = $senti->main("agnezmo pintar dan cantik sekali tetapi lintah darat :)");

echo $hasil['kelas'];
// Positif

//menampilkan hasil perhitungan
print_r($hasil);
/*
Array
(
    [classified_text] => agnezmo pintar [4] dan cantik [6] sekali tetapi lintah darat [-4] :) [3]
    [tweet_text] =>  agnezmo pintar dan cantik sekali tetapi lintah darat :)
    [sentence_score] => Array
        (
            [0] => agnezmo pintar [4] dan cantik [6] sekali tetapi lintah darat [-4] :) [3]
        )

    [max_positive] => 6
    [max_negative] => -4
    [kelas] => Positif
)
*/
```
## Pustaka
### algoritma
Algoritma yang digunakan pada library ini adalah hak intelektual masing-masing pemiliknya yang tertera di bawah ini. Lalu untuk meningkatkan kualitas kode, algoritma tersebut diterapkan ke dalam Object Oriented Design.Silakan kutip makalah ini jika Anda menggunakan program ini:
- Wahid, D. H., & Azhari, S. N. (2016). Peringkasan Sentimen Esktraktif di Twitter Menggunakan Hybrid TF-IDF dan Cosine Similarity. IJCCS (Indonesian Journal of Computing and Cybernetics Systems), 10(2), 207-218.
### Kamus
Kamus/leksikon SentiStrength ini diperoleh dari [sentistrength_id](https://github.com/masdevid/sentistrength_id)
