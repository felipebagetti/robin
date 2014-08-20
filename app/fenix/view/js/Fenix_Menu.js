//@include "/app/fenix/view/js/Fenix.js"

Fenix_Menu = {};

Fenix_Menu.formatterName = function(text, record, column, grid, table, tr, td){
    if(record._level > 0){
        text = "".lpad("&nbsp;&nbsp;&nbsp;", record._level*18) + " &rarr; " + text;
    }
    return text;
}
