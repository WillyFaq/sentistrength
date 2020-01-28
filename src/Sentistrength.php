<?php
/*
 * (c) WillyFaq <willyfaqurokhim@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// If you don't to add a custom vendor folder, then use the simple class
// namespace wfphpnlp;

namespace wfphpnlp;

class Sentistrength
{
	
	private $negation_conf = true;
	private $booster_conf = true;
	private $ungkapan_conf = true;
	private $consecutive_conf = true;
	private $repeated_conf = true;
	private $emoticon_conf = true;
	private $question_conf = true;
	private $exclamation_conf = true;
	private $punctuation_conf = true;

	private $negasi = [];
	private $tanya = [];
	private $sentiwords_dict = [];
	private $emoticon_dict = [];
	private $idioms_dict = [];
	private $boosterwords_dict = [];

	private $neutral_term = [];
	private $kalimat_max_neg = -1;
	private $kalimat_max_pos = 1;
	private $kalimat_score = [];
	private $kalimat_text = [];

	private $max_neg = -1;
    private $max_pos = 1;
    private $mean_neg = [1];
    private $mean_pos = [1];
    private $sentence_score=[];
    private $is_tanya = False;
    private $sentence_text = '';

    private $prev_score = 0;
    private $pre_max_pos = [];
    private $pre_max_neg = [];

	private $score = 0;

	private $mean_conf = false;

	private $sentences_text = [];
  	private $sentences_score = [];
	private $sentences_max_pos = 0;
  	private $sentences_max_neg = 0;
  	private $sentence_result = "";

	public function __construct($config=[]) {
    	if(!empty($config)){
    		foreach ($config as $i => $v) {
    			$this->{$i} = $v;
    		}
    	}
    	$dictionaryFile = __DIR__ . '/../kamus';
    	$this->negasi 				=  $this->make_simple_dict("$dictionaryFile/negatingword.txt");
    	$this->tanya 				=  $this->make_simple_dict("$dictionaryFile/questionword.txt");
    	
    	$this->sentiwords_dict 		=  $this->make_dict("$dictionaryFile/sentiwords_id.txt");
    	$this->emoticon_dict 		=  $this->make_dict_emo("$dictionaryFile/emoticon_id.txt");
    	$this->idioms_dict 			=  $this->make_dict("$dictionaryFile/idioms_id.txt");
    	$this->boosterwords_dict 	=  $this->make_dict("$dictionaryFile/boosterwords_id.txt");

    	$this->mean_conf = false;
  	}

  	public function main($input=""){
  		$this->neutral_term = ['jika','kalau'];
		$kalimat = explode(".", $input);
		$this->kalimat_max_neg = -1;
		$this->kalimat_max_pos = 1;
		$this->kalimat_score = [];
		$this->kalimat_text = [];

		foreach ($kalimat as $k => $v) {
  			$this->max_neg = -1;
		    $this->max_pos = 1;
		    $this->mean_neg = [1];
		    $this->mean_pos = [1];
		    $this->sentence_score=[];

		    $terms = explode(" ", $v);
		    $terms_length = sizeof($terms);
		    
		    $this->is_tanya = false;
		    $this->sentence_text = '';

		    #SEMUA KALIMAT YANG MEMILIKI TANDA SERU MEMILIKI +ve minimal 2
		    if ($this->exclamation_conf && strstr($v, '!')) {
		    	$this->max_pos = 2;
		    } 

		    $this->prev_score = 0;
		    $this->pre_max_pos = [];
		    $this->pre_max_neg = [];
		    foreach ($terms as $i => $term) {
		    	$is_extra_char = false;
		        $plural = '';
		        $this->score = 0;
		        // mencari huruf yang double lebih dari 3 contoh : kereeeen
		        if(preg_match('/([A-Za-z])\1{3,}/', $term)){
		        	$is_extra_char = false;
		        }
		        // merubah huruf yang double lebih dari 3 menjadi normal contoh : kereeeen -> keren
        		$term = $this->remove_extra_repeated_char($term);
        		// mencari kata plular dan merubah menjadi singular contoh : laki-laki -> laki
		        $plural = '';
		        if( preg_match('/([A-Za-z]+)\-\1/', $term) ){
		            $plural = $term;
		            $term = $this->plural_to_singular($term);
		        }

		        $this->score = $this->senti($term);
		        //echo "$term = $this->score <br>";
		        #NEGATION HANDLER#
		        if ($this->negation_conf && $this->score !=0 && $i>1){
		        	$this->cek_negationword($terms[$i-1],$terms[$i-2]);
		    	}
		        //echo "$term = $this->score <br>";
		        
		        #BOOSTERWORD HANDLER#
		        if ($this->booster_conf && $this->score !=0 && $i>0 && $i<=($terms_length-1) ){
		        	$this->cek_boosterword($terms[$i-1]);	
		        }
		        if ($this->booster_conf && $this->score !=0 && $i>=0 && $i<($terms_length-1) ){
		        	$this->cek_boosterword($terms[$i+1]);	
		        } 
		        //echo "$term = $this->score <br>";

		        #IDIOM/UNGKAPAN HANDLER#
                if($this->ungkapan_conf && $i>1 && $i<($terms_length-1)){
                	$this->cek_ungkapan([$terms[$i-1],$term],[$terms[$i-2],$terms[$i-1],$term],$i);
                }
		        //echo "$term = $this->score <br>";

                #CONSECUTIVE SENTIMENT WORD#
                if($this->consecutive_conf && $i>0 && $i<=($terms_length-1) && $this->score!=0){
                	$this->cek_consecutive_term($terms[$i - 1]);
                }
                
                #+1 SENTI SCORE IF REPEATED CHAR ON POSITIVE/NEGATIVE +2 IF NEUTRAL TERM
                if($this->repeated_conf && $is_extra_char==true && $this->score>0){$this->score+=1;}
                if($this->repeated_conf && $is_extra_char==true && $this->score<0){$this->score-=1;}
                if($this->repeated_conf && $is_extra_char==true && $this->score==0){$this->score=1;}

                if($this->punctuation_conf && $i>=0 && $i<($terms_length-1)){ $this->cek_repeated_punctuation($terms[$i+1]); }
                # CEK APAKAH TERDAPAT KATA TANYA
                if($this->question_conf && (in_array($term, $this->tanya) || preg_match('/\?/', $term)) ){$this->is_tanya = true; }
                # CEK neutral term 
            	if($this->score!=0 && $i>1 && $i<($terms_length-2)){$this->cek_neutral_term($terms, $i);}

            	if( $this->emoticon_conf && $this->score==0 ){ $this->score = $this->emosikon($term); }

            	$this->prev_score = $this->score;
            	if($this->mean_conf && $this->score>0 ){ array_push($this->mean_pos, $this->score); }
            	if($this->mean_conf && $this->score<0 ){ array_push($this->mean_neg, abs($this->score)); }
                #GET MAX SCORE +ve/-ve	
            	$this->max_pos = ($this->score > $this->max_pos)?$this->score:$this->max_pos;
            	$this->max_neg = ($this->score < $this->max_neg)?$this->score:$this->max_neg;
                #insert score info current term
            	array_push($this->pre_max_pos, $this->max_pos);
            	array_push($this->pre_max_neg, $this->max_neg);

            	if($plural != ''){$term = $plural;}
            	$this->sentence_text .= " ".$term;
            	if( $this->score != 0 ){ $term = "$term [$this->score]"; }
            	array_push($this->sentence_score, $term);
  			}

  			array_push($this->sentences_text, $this->sentence_text);
  			array_push($this->sentences_score, join(" ", $this->sentence_score) );

  			if($this->is_tanya){$this->max_neg = -1;}

  			$this->sentences_max_pos = ($this->max_pos > $this->sentences_max_pos)?$this->max_pos:$this->sentences_max_pos;
  			$this->sentences_max_neg = ($this->max_neg < $this->sentences_max_neg)?$this->max_neg:$this->sentences_max_neg;
  		}

  		$this->sentence_result = $this->classify();
        # print self.sentences_text
        return array(
        		"classified_text" => join(". ", $this->sentences_score),
        		"tweet_text" => join(". ", $this->sentences_text),
        		"sentence_score" => $this->sentences_score,
        		"max_positive" => $this->sentences_max_pos,
        		"max_negative" => $this->sentences_max_neg,
        		"kelas" => $this->sentence_result,
        		);

  	}


	private function senti($term){
		$ret = 0;
		foreach ($this->sentiwords_dict as $k => $v) {
			if($v[0] == $term){
				$ret = $v[1];
			}
		}
		return $ret;
	}

	private function emosikon($term){
		$ret = 0;
		foreach ($this->emoticon_dict as $k => $v) {
			if($v[0] == $term){
				$ret = $v[1];
			}
		}
		return $ret;
	}

	private function ungkapan($term){
		$ret = 0;
		foreach ($this->idioms_dict as $k => $v) {
			if($v[0] == $term){
				$ret = $v[1];
			}
		}
		return $ret;
	}

	private function booster($term){
		$ret = 0;
		foreach ($this->boosterwords_dict as $k => $v) {
			if($v[0] == $term){
				$ret = $v[1];
			}
		}
		return $ret;
	}

	private function remove_extra_repeated_char($term){
		return preg_replace('/([A-Za-z])\1{2,}/', '$1', $term);
	}


	private function plural_to_singular($term){
		return preg_replace('/([A-Za-z]+)\-\1/', '$1', $term);
	}

	private function cek_negationword($prev_term, $prev_term2){
		#jika kata sebelumnya (index-1) adalah kata negasi, negasikan nilai -+nya
	    if(in_array($prev_term, $this->negasi) || in_array($prev_term2." ".$prev_term, $this->negasi)){
	    	$this->score =  -i * $this->score;
	    }
	}

	private function cek_boosterword($term){	
        $booster_score = $this->booster($term);
        if ($booster_score !=0 && $this->score>0){ $this->score += $booster_score; } 
        if ($booster_score !=0 && $this->score<0){ $this->score -= $booster_score; } 
	}

	private function cek_consecutive_term($prev_term){
		if($this->prev_score>0 && $this->score>=3){ $this->score += 1; }
		if($this->prev_score<0 && $this->score<=-3){ $this->score -= 1; }
	}

	private function cek_ungkapan($bigram, $trigram, $i){
		$bigram = join(" ", $bigram);
        $trigram = join(" ", $trigram);
        $ungkapan_score = $this->ungkapan($bigram);
        //echo "$bigram -> $trigram -> $ungkapan_score ----> <br>";
        if($ungkapan_score == 0){
        	$ungkapan_score = $this->ungkapan($trigram);
        }
        if($ungkapan_score != 0){
        	$this->score = $ungkapan_score;
        	$this->prev_score = 0;
        	$this->pre_max_pos[$i - 1] = 1;
        	$this->pre_max_neg[$i - 1] = -1;
        	$this->max_pos = $this->pre_max_pos[$i - 2];
        	$this->max_neg = $this->pre_max_neg[$i - 2];
        	$this->sentence_score[$i - 1] = preg_replace('/\[\d\]/', '', $this->sentence_score[$i - 1] );
        }
	} 

	private function cek_repeated_punctuation($next_term){
		if( preg_match('/!{2,}/', $next_term) && $this->score >= 3 ){ $this->score += 1; }
		if( preg_match('/!{2,}/', $next_term) && $this->score <=-3 ){ $this->score -= 1; }
	}
	
	private function cek_neutral_term($term, $i){
		if( in_array($term[$i-1], $this->neutral_term) || in_array($term[$i+1], $this->neutral_term) ){$this->score=1;}
	}


	private function classify(){
		$result = "neutral";	

		if($this->mean_conf){
			$mean_p = mean($this->mean_pos);
			$mean_n = mean($this->mean_neg);

			if($mean_p > $mean_n){
				$result = "Positif";
			}else if($mean_p < $mean_n && !$this->is_tanya){
				$result = "Negatif";
			}else if($mean_p < $mean_n && $this->is_tanya){
				$result = "Netral";
			}
		}else{
			if( abs($this->sentences_max_pos) > abs($this->sentences_max_neg) ){
				$result = "Positif";
			}else if( abs($this->sentences_max_pos) < abs($this->sentences_max_neg) ){
				$result = "Negatif";
			}else if( abs($this->sentences_max_pos) == abs($this->sentences_max_neg) ){
				$result = "Netral";
			}
		}

		return $result; 
	}

	private function mean($arr){
		return array_sum($arr) / count($arr);
	}

  	public  function make_dict($fn){
		$myfile = fopen($fn, "r") or die("File kamus tidak ada. Tampaknya instalasi Anda rusak.");
		$a = fread($myfile,filesize($fn));
		$b = explode("\n", $a);
		fclose($myfile);
		$dict_arr = [];
		foreach ($b as $k => $v) {
			$c =  explode(":", $v);
			
			array_push($dict_arr, array($c[0], (int)$c[1]));
		}
		return $dict_arr;
	}

	
  	public  function make_dict_emo($fn){
		$myfile = fopen($fn, "r") or die("File kamus tidak ada. Tampaknya instalasi Anda rusak.");
		$a = fread($myfile,filesize($fn));
		$b = explode("\n", $a);
		fclose($myfile);
		$dict_arr = [];
		foreach ($b as $k => $v) {
			$c =  explode(" | ", $v);
			
			array_push($dict_arr, array($c[0], (int)$c[1]));
		}
		return $dict_arr;
	}

	public function make_simple_dict($fn){
		$myfile = fopen($fn, "r") or die("File kamus tidak ada. Tampaknya instalasi Anda rusak.");
		$a = fread($myfile,filesize($fn));
		$b = explode("\n", $a);
		fclose($myfile);
		return $b;
	}

	public function test($input){
		return $input;
	}

	public function set_result($input){
		$this->sentence_result = $input;
	}

	public function get_result(){
		return $this->sentence_result;
	}

}  