// JS for search/filter/pagination on logs page
$(function() {
  function filterTable($table, term) {
    term = term.toLowerCase();
    $table.find('tbody tr').each(function() {
      var rowText = $(this).text().toLowerCase();
      $(this).toggle(rowText.includes(term));
    });
  }
  $('.log-search').on('input', function() {
    var tab = $(this).data('tab');
    var term = $(this).val();
    filterTable($('#' + tab + '-table'), term);
  });
  // Simple client-side pagination
  $('.log-table').each(function() {
    var $table = $(this);
    var rowsPerPage = 20;
    var $rows = $table.find('tbody tr');
    var totalRows = $rows.length;
    var $pager = $('<div class="pager"></div>');
    $table.after($pager);
    function showPage(page) {
      $rows.hide();
      $rows.slice((page-1)*rowsPerPage, page*rowsPerPage).show();
      $pager.find('button').removeClass('active');
      $pager.find('button[data-page="'+page+'"]').addClass('active');
    }
    var pages = Math.ceil(totalRows/rowsPerPage);
    for (var i=1; i<=pages; i++) {
      $pager.append('<button data-page="'+i+'">'+i+'</button> ');
    }
    $pager.on('click', 'button', function() {
      showPage($(this).data('page'));
    });
    showPage(1);
  });
});
