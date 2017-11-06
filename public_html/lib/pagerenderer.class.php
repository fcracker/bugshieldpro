<?php
class PageRenderer
{
	/*private _template;
	private _templateRoot;
	private _templateUrl;
	private _templateContent;
	private _title;
	private _head;
	private _menu;
	private _content;
	private _renderedPage;*/
	
	public function __construct($tmpl)
	{
		global $cfg;
		$this->_baseroot =  $cfg['site']['url'] . $cfg['site']['folder'];
		$this->_templateRoot = $cfg['site']['trainingAreaTemplateFolder'];
		$this->_templateUrl = $cfg['site']['trainingAreaTemplateUrl'];
		$this->setTemplate($tmpl);
		$this->_head = "";
		$this->_title = "";
		$this->_content = "";
		$this->_renderedPage = "";
		$this->_menu = "";
	}
	
	public function setTemplate($tmpl)
	{
		$this->_template = $tmpl;
	}
	
	public function setMenu($menu = array())
	{
		$this->_menu = $menu;
	}
	
	public function setTitle($title)
	{
		$this->_title = $title;
	}
	
	public function setHead($head)
	{
		$this->_head = $head;
	}
	
	public function setContent($content)
	{
		$this->_content = $content;
	}
	
	private function _renderMenu($menu = array())
	{
		global $cfg;
		if(isset($menu) && !is_array($menu)) return "";
		$html = "";
		foreach($menu as $menuitem=>$link)
		{
			if(is_array($link))
			{
				
				$html .= "<li>";
				if($menuitem==$cfg['site']['MyProfile']) $html .= "<a href=\"" . sess_url($this->_baseroot . "profile.php")."\">$menuitem</a>";
				else $html .= "<a>$menuitem</a>";
				$html .= $this->_renderMenu($link);
				$html .= "</li>";
			}
			else
			{
				$html .= "<li>";
				$html .= "<a href=\"".sess_url($this->_baseroot .$link)."\">$menuitem</a>";
				$html .= "</li>";
			}
		}
		
		if ($html != "") $html = "<ul>$html</ul>";
		
		return $html;
	}
	
	private function _readTemplate()
	{
		$days = isset($_COOKIE["days"]) && $_COOKIE["days"] == 30 ? 30 : 365;
		$alt = isset($_COOKIE["alt"]) && $_COOKIE["alt"] == 1 ? 1 : 0;
		$tfile = $this->_templateRoot . $this->_template . "/template" . $days . "_" . $alt . ".html";
		return file_get_contents($tfile);
	}
	
	public function render()
	{
		$url = $this->_templateUrl . $this->_template . "/";
		$this->_renderedPage = $this->_readTemplate();
		$this->_renderedPage = str_replace("{:root:}",  $this->_baseroot, $this->_renderedPage);
		$this->_renderedPage = str_replace("{:base:}",  $url, $this->_renderedPage);
		$this->_renderedPage = str_replace("{:title:}",  $this->_title, $this->_renderedPage);
		$this->_renderedPage = str_replace("{:head:}",  $this->_head, $this->_renderedPage);
		$this->_renderedPage = str_replace("{:menu:}", $this->_renderMenu($this->_menu), $this->_renderedPage);
		$this->_renderedPage = str_replace("{:content:}", $this->_content, $this->_renderedPage);
	}
	
	public function display()
	{
		echo $this->_renderedPage;
	}
}
?>