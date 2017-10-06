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
  <script>
    document.getElementById('task-date').onclick = toggleTaskDateEditor;
    document.getElementById('date-editor').getElementsByClassName('close')[0].onclick = toggleTaskDateEditor;

    function toggleTaskDateEditor(){
      document.getElementById('date-editor').classList.toggle('visible');
    }
  </script>
  </body>

</html>