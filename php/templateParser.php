<?php
//
// templateParser
// helps in separating layout/content
//

class templateParser {
        var $output;

		function templateParser($templateFile='default_template.htm'){
              (file_exists($templateFile))?$this->output=file_get_contents($templateFile):die('Error:Template file '.$templateFile.' not found');
        }

        function parseTemplate($tags=array()){
              if(count($tags)>0){
                   foreach($tags as $tag=>$data){
                        $data=(file_exists($data))?$this->parseFile($data):$data;
                        $this->output=str_replace('{'.$tag.'}',$data,$this->output);
                   }
              }
              else {
                   die('Error: No tags were provided for replacement');
              }
        }

        function parseFile($file){
              ob_start();
              include($file);
              $content=ob_get_contents();
              ob_end_clean();
              return $content;
        }

        function display(){
              return $this->output;
        }
    }
?>