<?php

class CliProgressBar {
    /* total number of tasks */

    var $total = null;
    /* current task  current of total */
    var $current = null;
    var $progress = null;
    var $bartotal = null;
    var $barprogress = null;
    var $start_time = null;
    var $clean = null;

    public function initPBar($task = 10, $bar = 20) {

        $this->total = $task;
        $this->bartotal = $bar;

        $this->progress = 0;
        $this->start_time = time();
        $a = "[-] " . str_repeat("-", $this->bartotal) . ' inizio ' . $this->percentPBar(0) . '% ';
        // echo(strlen($a));
        // exit();
        echo "[-] " . str_repeat("-", $this->bartotal) . ' inizio ' . $this->percentPBar(0) . '% ';
        return $this->total;
    }

    public function advancePBar($current, $desc) {
//echo "c:".$current."\n\n"; 
// for($i=0;$i<=$current ;$i++)    {     
        $pct = $this->percentPBar($current);
        $barcurrent = $this->perPBar($current);
        if ($barcurrent < 1) {
            $barcurrent = 1;
        }
        if ($this->bartotal < $barcurrent) {
            $barcurrent = $this->bartotal;
        }
        $todo = $this->total - $current;
        $bartodo = $this->perPBar($todo);
        $bartodo = $this->bartotal - $barcurrent;

        $bardone = str_repeat(chr(219), $barcurrent);
        $this->progress = $current;
        $elapsed = time() - $this->start_time;
        // ETA 
        $rate = (time() - $this->start_time) / $barcurrent;
        $left = $this->bartotal - $barcurrent;
        $eta = round($rate * $left, 2);
        /*
          echo("\npct:").$pct;
          echo('barpct:').$barcurrent;
          echo('bartodo:').$bartodo;
          echo('bartotal:').$this->bartotal."\n\n"; ;
          echo  substr()
         */
        $desc = str_pad($desc, 30);
        $desc = substr($desc, 0, 30);
        //echo "\n\n".strlen($desc).":".$desc."\n\n";
        if ($current == $this->total) {
            $stringa = "\r[+] " . $bardone . str_repeat(chr(178), $bartodo) . " " . $desc . " " . $pct . "% " . $this->progress . "/" . $this->total . " ela:" . $elapsed . " s";
            print "\r[*] " . $stringa . str_repeat(" ", (80 - strlen(substr($stringa, 0, 80))));
        } else {
            $stringa = "\r[*] " . $bardone . str_repeat(chr(178), $bartodo) . " " . $desc . " " . $pct . "% " . $this->progress . "/" . $this->total . " eta:" . $eta . " s" . " ela:" . $elapsed;
            //print "\r\t[*] ".$bardone .str_repeat("²", $bartodo )." ".$desc." ".$pct."% (".$this->progress ." \ ". $this->total.") elapsed ".$elapsed;    
            print "\r[*] " . $stringa . str_repeat(" ", (80 - strlen(substr($stringa, 0, 80))));
        }

//task's simulation       
        //usleep(mt_rand(300000,900000));     
        //usleep(mt_rand(600000,1200000));     
    }

    public function finishPBar() {
        //echo 'bt:'.$this->total .' bp:'. $this->progress;  
        //if ($this->total > $this->progress){   
        $this->advancePBar($this->total, 'Completed ');

        // echo chr(033)."[2J";  
        //}   
    }

    public function percentPBar($processed) {
        return $this->progress = round($processed / ( $this->total / 100 ));
    }

    public function perPBar($processed) {
        return $this->barprogress = round(($processed / $this->total ) * $this->bartotal);
    }

}