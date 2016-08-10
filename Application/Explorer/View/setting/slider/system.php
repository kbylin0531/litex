<?php
	function check_first_in($value){
		if ($value == $GLOBALS['config.setting_system.first_in']) {
			echo 'checked="checked"';
		}
	}
?>
<div class='h1'><i class="font-icon icon-user"></i>{$Think.L.system_setting}</div>
<div class="nav">
    <a href="javascript:;" class="this" data-page="setting">{$Think.L.system_setting}</a>
    <a href="javascript:;" class="" data-page="setting_menu">{$Think.L.system_setting_menu}</a>
    <div style="clear:both;"></div>
</div>

<div class="section setting system_setting">
	<div class="box_line">
	    <span class='infotitle'>{$Think.L.system_name}:</span><input type="text" name="system_name" 
      value="{$Think.L.config.setting_system.system_name}" />
	    <i>{$Think.L.system_name_desc}</i>
	</div>
	<div class="box_line">
		<span class='infotitle'>{$Think.L.system_desc}:</span><input type="text" name="system_desc" 
    value="{$Think.L.config.setting_system.system_desc}" />
		<i>{$Think.L.system_desc}</i>
	</div>
	<div class="box_line">
		<span class='infotitle'>{$Think.L.path_hidden}:</span><input type="text" name="path_hidden" 
      value="{$Think.L.config.setting_system.path_hidden}" />
		<i>{$Think.L.path_hidden_desc}</i>
	</div>
	<div class="box_line">
		<span class='infotitle'>{$Think.L.new_user_folder}:</span>
		<input type="text" name="new_user_folder" value="{$Think.L.config.setting_system.new_user_folder}" />
		<i>{$Think.L.new_user_folder_desc}</i>
	</div>
	<div class="box_line">
		<span class='infotitle'>{$Think.L.new_user_app}:</span>
		<input type="text" name="new_user_app" 
			value="{$Think.L.config.setting_system.new_user_app}"/>
        <i>{$Think.L.new_user_app_desc}</i>
	</div>
	<div class="box_line">
		<span class='infotitle'>{$Think.L.auto_login}:</span>
			<label>
			<input type="checkbox" name="auto_login" 
		      <?php if($config['setting_system.auto_login']=="1") echo 'checked="checked"';?> />
		    <i>{$Think.L.auto_login_desc}</i>
		    </label>		
	</div>
	<div class="box_line">
		<span class='infotitle'>{$Think.L.first_in}:</span>
		<label><input type="radio" name="first_in" value="desktop" <?php check_first_in('desktop');?> />{$Think.L.ui_desktop}</label>
		<label><input type="radio" name="first_in" value="explorer" <?php check_first_in('explorer');?> />{$Think.L.ui_explorer}</label>
		<label><input type="radio" name="first_in" value="editor" <?php check_first_in('editor');?> />{$Think.L.ui_editor}</label>
	  <div style="clear:both"></div>
  </div>
  <div class="box_line">
    <a href="javascript:void(0);" class="system_save button">{$Think.L.button_save}</a>
  </div>
  <div style="clear:both;"></div>
</div>

<div class="section setting_menu hidden">
    <table id="list" align="center" border="0" cellspacing="0" cellpadding="0">
	    <tbody><tr class="title"><td width="10%">{$Think.L.menu_name}</td><td>{$Think.L.url_path}<span>({$Think.L.url_path_desc})</span></td><td>{$Think.L.action}</td></tr>
        <?php 
         $config['setting_system.menu'][] = array('name'=>'','type'=>'','url'=>'','target'=>'_blank','use'=>'1');
         foreach($config['setting_system.menu'] as $key => $menu) { 
              $the_type = $menu['type']=='system'?' menu_system ':'';
              $the_use = $menu['use']=='1'?' menu_show ':'menu_hidden';
              $the_null = $menu['name']==''?' menu_default hidden ':'';
        ?>
        <tr class="menu_list <?php echo $the_type.$the_use.$the_null;?>">
            <td class="name"><input type="text" name="name" value="<?php echo htmlspecialchars(urldecode($menu['name']));?>"/>
                <span>{$Think.L.ui_'.$menu['name']];?></span>
            </td>
            <td class="url">
                <input type="text" name="url" value="<?php echo htmlspecialchars(urldecode($menu['url']));?>">
                <span><?php echo $menu['name}</span>
                <label><input type="checkbox" name="target" value="<?php echo $menu['target}" 
                    <?php if($menu['target']=='_blank'){echo "checked='checked'";}?> />{$Think.L.menu_open_window}</label> 
            </td>
            <td class="action">
            	<a href="javascript:void(0)" class="button move_up icon-arrow-up"></a>
            	<a href="javascript:void(0)" class="button move_down icon-arrow-down"></a>
            	<a href="javascript:void(0)" class="button move_hidden">
                    <?php echo $menu['use']=='1'?$L['menu_hidden']:$L['menu_show}
                </a>
                <a href="javascript:void(0)" class="button move_del">{$Think.L.menu_move_del}</a>
            </td>
    	</tr>
        <?php } ?>
		</tbody>
    </table>
    <a href="javascript:void(0)" class="add system_menu_add"><i class="icon-plus pr-10"></i>{$Think.L.button_add}</a>
    <a href="javascript:void(0);" class="system_menu_save button">{$Think.L.button_save}</a>
</div>

<?php
	$error = php_env_check();
	if ($error!='' && $GLOBALS['is_root'] == 1) {
		$info = '<a href="javascript:;" class="button warning path_ext_tips"><i class="icon-warning-sign"></i>phpinfo</a>';
		echo '<div class="alert alert-warning" role="alert"><h3>'.$L['php_env_error'].$info.'</h3>'.$error.'</div>';
	}
?>



