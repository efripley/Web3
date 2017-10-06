<?php global $CONFIG; ?>
  <div id="date-editor" style="display: none;" class="editor-cmp">
    <div class="wrap">
      <div class="close">&lsaquo;</div>
      <form action="<?php echo $CONFIG['url']; ?>" method="GET">
        <input type="hidden" name="task" value="<?php echo $_GET['task']?>">
        <input type="date" name="data">
        <input type="submit" name="submit" value="Change Date">
      </form>
    </div>
  </div>
  <div id="time-editor" style="display: none;" class="editor-cmp">
    <div class="wrap">
      <div class="close">&lsaquo;</div>
      <form action="<?php echo $CONFIG['url']; ?>" method="GET">
        <input type="hidden" name="task" value="<?php echo $_GET['task']?>">
        <input type="number" name="data" value="<?php echo $currentTask['task_time'];?>">
        <input type="submit" name="submit" value="Change Time">
      </form>
    </div>
  </div>
  <script>
    if(document.getElementById('task-date')){
      document.getElementById('task-date').onclick = toggleTaskDateEditor;
      document.getElementById('date-editor').getElementsByClassName('close')[0].onclick = toggleTaskDateEditor;
    }

    function toggleTaskDateEditor(){
      document.getElementById('date-editor').classList.toggle('visible');
    }

    if(document.getElementById('task-time')){
      document.getElementById('task-time').onclick = toggleTaskDateEditor;
      document.getElementById('time-editor').getElementsByClassName('close')[0].onclick = toggleTaskDateEditor;
    }

    function toggleTaskDateEditor(){
      document.getElementById('time-editor').classList.toggle('visible');
    }    
  </script>
  </body>

</html>