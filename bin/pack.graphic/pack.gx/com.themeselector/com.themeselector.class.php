<?php
namespace Graphic\GX;

class ThemeSelector extends \Graphic\Controls\Control{
    private $themes;
    protected $defualtOptions = array(
        'value' => '', 'visuality' => 'border', 'multitheme' => true,
        'selectlocation' => true, 'multilocation' => false
    );
    static public $init = false;
    
    public function __construct($name, $options = array()){
        global $DataModule;
        parent::__construct($name, $options);
        $this->prepareDefaults();
        $this->themes = $DataModule->themes->all;
        
        $this->clientCalls = array('value' => '$("#'.$this->id.'").val()');
    }
    
    private function prepareDefaults(){
        $value = $this->options->value;
        
        if(empty($value)) $value = array();
        
        if(is_object($value))
            $value = get_object_vars($value);
            
        foreach($value as $i => $v){
            $n = $i;
            if(is_numeric($i)){
                $value[$v] = array();
                unset($value[$i]);
                $n = $v;
            }
            if(is_object($value[$n]))
                $value[$n] = get_object_vars($value[$n]);
            
            extend($value[$n], array(
                'frame' => 'noframe', 'positioning' => 'static', 'position' => '[i18n:align]',
                'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0',
                'width' => '0', 'height' => '0', 'locations' => array()
            ));
            
            $value[$n] = (object)$value[$n];
        }
        
        $this->options->value = (object)$value;
    }

    protected function generate(){
        global $Viewer, $i18n; $maps = '';
        $Viewer->bind('onready',
            '$("#'.$this->id.'_fieldset").themeSelector({'.
                'selectlocation: '.($this->options->selectlocation? 'true,': 'false,').
                'multitheme: '.($this->options->multitheme? 'true,': 'false,').
                'multilocation: '.($this->options->multilocation? 'true': 'false').
            '});'
        );
        
        if(!ThemeSelector::$init){
            ThemeSelector::$init = true;
            $Viewer->append('
                <div id="themeselector-popup" class="gxui-popup gxui-flat gxui-light" data-name="">
                    <div class="header gxui-flat gxui-dark">
                        <span></span>
                        <a class="ui-icon ui-icon-check" title="[i18n:com_themeselector_save]"></a>
                        <a class="ui-icon ui-icon-close" title="[i18n:com_themeselector_cancel]"></a>
                    </div>
                    <select class="map-option-locs" multiple="true"></select>
                    <div class="map-option">
                        <div class="map-option-frame">
                            <label class="gxui-inline-block"><b>'.$i18n->widget_frame.' :</b></label>
                            <select class="gxui-border"><option value="noframe">'.$i18n->widget_noframe.'</option></select>
                        </div>
                        <div class="map-option-fw">
                            <label class="gxui-inline-block"><b>'.$i18n->widget_width.' :</b></label>
                            <input class="gxui-border">
                        </div>
                        <div class="map-option-fh">
                            <label class="gxui-inline-block"><b>'.$i18n->widget_height.' :</b></label>
                            <input class="gxui-border">
                        </div>
                        <div class="map-option-margin">
                            <label class="gxui-inline-block"><b>'.$i18n->widget_margin.' :</b></label>
                            <input class="gxui-border">
                        </div>
                        <div class="map-option-positioning">
                            <label class="gxui-inline-block"><b>'.$i18n->positioning.' :</b></label>
                            <select class="gxui-border">
                                <option value="static">'.$i18n->pos_static.'</option>
                                <option value="float">'.$i18n->pos_float.'</option>
                            </select>
                        </div>
                        <div class="map-option-position">
                            <label class="gxui-inline-block"><b>'.$i18n->position.' :</b></label>
                            <select class="gxui-border">
                                <option value="right">'.$i18n->pos_float_r.'</option>
                                <option value="center">'.$i18n->pos_float_c.'</option>
                                <option value="left">'.$i18n->pos_float_l.'</option>
                                <option value="absolute">'.$i18n->pos_coord.'</option>
                            </select>
                        </div>
                        <div class="map-option-coords">
                            <div class="field-row"><label class="gxui-inline-block"><b>'.$i18n->pos_coord_t.' : </b></label><input class="gxui-border coord_t"></div>
                            <div class="field-row"><label class="gxui-inline-block"><b>'.$i18n->pos_coord_r.' : </b></label><input class="gxui-border coord_r"></div>
                            <div class="field-row"><label class="gxui-inline-block"><b>'.$i18n->pos_coord_b.' : </b></label><input class="gxui-border coord_b"></div>
                            <div class="field-row"><label class="gxui-inline-block"><b>'.$i18n->pos_coord_l.' : </b></label><input class="gxui-border coord_l"></div>
                        </div>
                    </div>
                    <div class="map-image"><img src="" class="gxui-border" /></div>
                </div>
            ');
        }
        
        foreach($this->themes as $data){
        	//debug($data);
        	$frames = \Utils\File::scandir('themes/'.$data->name.'/frames/', \Utils\File::SCAN_DIRS);
			
			$maps .= 
				'<map id="'.$this->id.'_'.$data->name.'_map" data-name="'.$data->name.'">'.
					'<span class="image">'.$data->preview.'</span>'.
					'<span class="frames">'.json_encode($frames).'</span>';
			
			foreach ($data->map as $name => $area)
				$maps .= '<area alt="'.$name.'" coords="'.$area['coords'].'" shape="'.$area['shape'].'"></area>';
			
			$maps .= '</map>';
        }
        
        $value = htmlspecialchars(json_encode($this->options->value));
        $selector =
            '<fieldset class="graphic-gx-themeselector gxui-flat gxui-light gxui-bordered" id="'.$this->id.'_fieldset">'.
                '<input name="'.$this->id.'" id="'.$this->id.'" value="'.$value.'">'.
                '<div class="header gxui-bevel">'.
                    '<a class="gxui-popuper" rel="#'.$this->id.'_sugg" href="javascript: void(0);">'.
                        '<span class="gxui-inline-block ui-icon ui-icon-carat-2-n-s"></span>'.$i18n->themes_list.
                    '</a>'.
                '</div>'.
                '<div class="content">'.
                    '<ul class="suggestions gxui-popup" id="'.$this->id.'_sugg" data-coords="5,1"></ul>'.
                    '<ul class="selected-items gxui-clearfix"></ul>'.
                '</div>'.
                $maps.
            '</fieldset>';
            
        return $selector;
    }
}
?>