{{ header }}{{ column_left }}
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-shipping" data-toggle="tooltip" title="{{ button_save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ cancel }}" data-toggle="tooltip" title="{{ button_cancel }}" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1>{{ heading_title }}</h1>
      <ul class="breadcrumb">
        {% for breadcrumb in breadcrumbs %}
        <li><a href="{{ breadcrumb.href }}">{{ breadcrumb.text }}</a></li>
        {% endfor %}
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    {% if error_warning %}
    <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> {{ error_warning }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    {% endif %}
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> {{ text_edit }}</h3>
      </div>
      <div class="panel-body">
        <form action="{{ action }}" method="post" enctype="multipart/form-data" id="form-shipping" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-cost">{{ entry_cost }}</label>
            <div class="col-sm-10">
              <input type="text" name="shipping_ukr_poshta_cost" value="{{ shipping_ukr_poshta_cost }}" placeholder="{{ entry_cost }}" id="input-cost" class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-tax-class">{{ entry_tax_class }}</label>
            <div class="col-sm-10">
              <select name="shipping_ukr_poshta_tax_class_id" id="input-tax-class" class="form-control">
                <option value="0">{{ text_none }}</option>
                {% for tax_class in tax_classes %}
                {% if tax_class.tax_class_id == shipping_ukr_poshta_tax_class_id %}
                <option value="{{ tax_class.tax_class_id }}" selected="selected">{{ tax_class.title }}</option>
                {% else %}
                <option value="{{ tax_class.tax_class_id }}">{{ tax_class.title }}</option>
                {% endif %}
                {% endfor %}
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-geo-zone">{{ entry_geo_zone }}</label>
            <div class="col-sm-10">
              <select name="shipping_ukr_poshta_geo_zone_id" id="input-geo-zone" class="form-control">
                <option value="0">{{ text_all_zones }}</option>
                {% for geo_zone in geo_zones %}
                {% if geo_zone.geo_zone_id == shipping_ukr_poshta_geo_zone_id %}
                <option value="{{ geo_zone.geo_zone_id }}" selected="selected">{{ geo_zone.name }}</option>
                {% else %}
                <option value="{{ geo_zone.geo_zone_id }}">{{ geo_zone.name }}</option>
                {% endif %}
                {% endfor %}
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status">{{ entry_status }}</label>
            <div class="col-sm-10">
              <select name="shipping_ukr_poshta_status" id="input-status" class="form-control">
                {% if shipping_ukr_poshta_status %}
                <option value="1" selected="selected">{{ text_enabled }}</option>
                <option value="0">{{ text_disabled }}</option>
                {% else %}
                <option value="1">{{ text_enabled }}</option>
                <option value="0" selected="selected">{{ text_disabled }}</option>
                {% endif %}
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-sort-order">{{ entry_sort_order }}</label>
            <div class="col-sm-10">
              <input type="text" name="shipping_ukr_poshta_sort_order" value="{{ shipping_ukr_poshta_sort_order }}" placeholder="{{ entry_sort_order }}" id="input-sort-order" class="form-control" />
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
{{ footer }} 