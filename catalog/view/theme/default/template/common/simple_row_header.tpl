<div class="form-group">
  <div class="col-sm-12">
    <?php if ($tag != '') { ?>
      <<?php echo $tag ?>><?php echo $label ?></<?php echo $tag ?>>
    <?php } else { ?>
      <?php echo $label ?>
    <?php } ?>    
  </div>
</div>