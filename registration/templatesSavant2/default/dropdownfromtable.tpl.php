<select name="<?php echo $this->inputname?>">
   <option value=""></option>
   <?php foreach($this->dropdown_arr as $dropoptions):?>
   <?php
	  $dropoptions['value'] = trim($dropoptions['value']);
	  unset ($selected);
	  if ($dropoptions['value'] == $this->post_value)
	  {
		 $selected = 'selected="selected"';
	  }
   ?>
   <option value="<?php echo trim($dropoptions['value'])?>" <?php echo $selected?>><?php echo trim($dropoptions['display'])?></option>
   <?php endforeach?>
</select>
