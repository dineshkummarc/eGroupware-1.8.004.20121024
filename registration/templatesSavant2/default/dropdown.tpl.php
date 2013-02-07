<select name="<?php echo $this->inputname?>">
   <option value=""></option>
   <?php foreach($this->values as $value):?>
   <?php
	  $value = trim ($value);
	  unset ($selected);
	  if ($value == $this->post_value)
	  {
		 $selected = 'selected="selected"';
	  }
   ?>
   <option value="<?php echo trim($value)?>" <?php echo $selected?>><?php echo trim($value)?></option>
   <?php endforeach?>
</select>


