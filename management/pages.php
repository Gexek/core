<?php
$grid = new Graphic\Grid('cats', array(
    'caption'       => $i18n->categorys,
    'multiselect'   => true,
    'server'        => 'management/ajax.php',
    'table'         => 'rewrites',
    'order'         => 'cRule ASC',
	'filter'		=> 'cType = \"page\" AND cOwnerType = \"user\"',
    'data'          => array(
        'manage'    => 'manage_pages'
    )
));

$cols = array();
$cols[] = new Graphic\GridColumn('cName', array('width'=>200, 'primary'=>true, 'hidden' => true, 'editable' => false));
$cols[] = new Graphic\GridColumn('cRule', array(
	'caption'=>$i18n->page_url, 'allownull' => false, 'width'=>250, 'dir'=>'ltr',
	'validation' => array(
			'pattern' => '^([a-zA-Z0-9\/\{\}]+)$',
			'patternmessage' => 'مقدار %s باید حتما ترکیبی از اعداد و حروف انگلیسی و یا کاراکتر های<strong>/{}</strong> باشد.'
	), 'align' => 'left'
));
$cols[] = new Graphic\GridColumn('cTitle', array('caption'=>$i18n->page_title, 'allownull' => false, 'width'=>300));
$cols[] = new Graphic\GridColumn('cDesc', array('caption'=>$i18n->page_desc, 'allownull' => true));
$cols[] = new Graphic\GridColumn('cKeywords', array('caption'=>$i18n->page_keywords, 'allownull' => true));

foreach($cols as &$col) $grid->addColumn($col);
echo $grid;
?>