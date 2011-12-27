<div class="ui-widget-content" style="border:none;">
  <ul class="rss">
  <?php foreach($this->items as $item) { ?>
    <li><a href="<?php echo $item['link']; ?>"><?php echo $item['label']; ?></a></li>
  <?php } ?>
</div>
<br />
