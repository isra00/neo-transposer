{% macro select(name, options, selected, other_attributes) %}
<select name="{{ name }}" id="{{ other_attributes.id|default(name) }}">
{% for value, label in options %}
	<option value="{{ value }}"{% if selected == value %} selected="selected"{% endif %}>{{ label|e }}</option>
{% endfor %}
</select>
{% endmacro %}