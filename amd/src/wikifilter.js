// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JavaScript for the wikifilter form class.
 *
 * @module    mod_wikifilter/wikifilter
 * @author    Annouar Faraman <annouar.faraman@umontreal.ca>
 * @copyright 2021 Université de Montréal
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, ajax, notification) {

    /**
     * Wikifilter associations list.
     */
    function AssociationsList() {
        this.table = $('#id_associations_table');
        this.tableClone = $('#table_clone');
        this.rowClone = $('.row_clone', this.tableClone);
        this.emptyRowClone = $('.empty_row_clone', this.tableClone);
        this.addRow = function(selectedRole, selectedTags) {
            var newRow = this.rowClone.clone();
            newRow.removeClass('row_clone');

            var newRowData = this.buildRowData(selectedRole, selectedTags);
            this.fillRow(newRow, newRowData);

            if ($('tr.empty_row').length) {
                $('tr.empty_row').remove();
            }

            $('tbody', this.table).append(newRow);
        };
        this.modifyRow = function(row, selectedRole, selectedTags) {
            var rowData = this.buildRowData(selectedRole, selectedTags);
            this.fillRow(row, rowData);
        };
        this.deleteRow = function(row) {
            row.remove();
            if (!$('tbody .actions', this.table).length) {
                var emptyRow = this.emptyRowClone.clone();
                emptyRow.removeClass('empty_row_clone').addClass('empty_row');
                $('tbody', this.table).append(emptyRow);
            }
        };
        this.empty = function() {
            if (!$('.empty_row', this.table).length) {
                $('tbody .actions', this.table).closest('tr').remove();
                var emptyRow = this.emptyRowClone.clone();
                emptyRow.removeClass('empty_row_clone').addClass('empty_row');
                $('tbody', this.table).append(emptyRow);
            }

        };
        this.buildRowData = function(selectedRole, selectedTags) {
            // Building role Cell.
            var roleCell = selectedRole.name;

            // Building tags and actions cells.
            var tagsCell = '';
            var actionsCell = '';
            selectedTags.each(function() {
                var thisTagElement = $(this);
                var thisTagId = thisTagElement.attr('data-value');
                var thisTagText = thisTagElement.text().replace('×', '');

                tagsCell += '<span class="badge badge-info mb-3 mr-1" data-id="' + selectedRole.id + '">' + thisTagText + '</span>';
                actionsCell += '<input name="associations[]" type="hidden" value="' + selectedRole.id + '-' + thisTagId + '">';
            });

            var rowData = {roleId: selectedRole.id, roleName: selectedRole.name,
                    roleCell: roleCell, tagsCell: tagsCell, actionsCell: actionsCell};

            return rowData;
        };
        this.fillRow = function(row, data) {
            $('.role', row).html(data.roleCell);
            $('.tags', row).html(data.tagsCell);
            $('.actions .db-data', row).html(data.actionsCell);
            $('.actions', row).attr('data-roleid', data.roleId);
            $('.actions', row).attr('data-rolename', data.roleName);
        };
    }

     /**
      * Wikifilter associations editor.
      */
    function AssociationsEditor() {
        this.associationsList = new AssociationsList();
        this.associationEditorModal = $('#association-editor-modal');
        this.roleSelectId = '#id_role';
        this.tagsSelectId = '#id_wikitags';
        this.addBtn = $('.add-btn', this.associationEditorModal);
        this.modifyBtn = $('.modify-btn', this.associationEditorModal);
        this.deleteBtn = $('.delete-icon');
        this.init = function() {
            this.events();
        };
        this.events = function() {
            var thisObject = this;
            var roleSelect = $(this.roleSelectId);

            this.associationEditorModal.on('show.bs.modal', function(e) {
                var callerBtn = $(e.relatedTarget);

                // Edit association.
                if (callerBtn.hasClass('edit')) {
                    var thisRoleId = callerBtn.closest('.actions').attr('data-roleid');
                    var thisRoleName = callerBtn.closest('.actions').attr('data-rolename');

                    // Show edit button.
                    thisObject.addBtn.addClass('hidden');
                    thisObject.modifyBtn.removeClass('hidden').attr('data-item', thisRoleId);

                    // Add role option to roleSelect and select it.
                    $('<option/>').val(thisRoleId).remove().html(thisRoleName).appendTo(thisObject.roleSelectId);
                    roleSelect.val(thisRoleId).attr('disabled', 'disabled');

                } else {
                    // Remove associationsList roles from roleSelect.
                    roleSelect.removeAttr('disabled');
                    $('.actions', thisObject.associationsList.table).each(function() {
                        var thisRoleId = $(this).attr('data-roleid');
                        roleSelect.find('option[value="' + thisRoleId + '"]').remove();
                    });
                }
            });
            this.associationEditorModal.on('hidden.bs.modal', function() {
                thisObject.addBtn.removeClass('hidden');
                thisObject.modifyBtn.addClass('hidden');
                thisObject.removeErrors();
                thisObject.resetTags();
            });

            this.addEvent();
            this.modifyEvent();
            this.deleteEvent();

        };
        this.addEvent = function() {
            var thisObject = this;
            var associationsList = this.associationsList;
            var associationEditorModal = this.associationEditorModal;
            var roleSelect = $(this.roleSelectId, associationEditorModal);

            // Adding addassociation button click event.
            this.addBtn.click(function() {
                var tagsSelection = $('.form-autocomplete-selection', associationEditorModal);

                // Validate associations form.
                var isValid = thisObject.validate(tagsSelection);
                if (isValid) {
                    // Adding new row.
                    var thisRoleId = roleSelect.val();
                    var thisRoleName = roleSelect.find('option:selected').text();

                    var selectedRole = {id: thisRoleId, name: thisRoleName};
                    var selectedTags = $('.badge', tagsSelection);
                    associationsList.addRow(selectedRole, selectedTags);

                    // Adding new row delete association button click event.
                    $('tr:last .delete-icon', associationsList.table).click(function() {
                        var thisRow = $(this).closest('tr');
                        thisObject.updateRolesSelect(thisRow);
                        associationsList.deleteRow(thisRow);
                    });

                    // Remove selected role from roleSelect.
                    roleSelect.find('option:selected').remove();

                    // Show modal.
                    associationEditorModal.modal('hide');

                } else {
                    tagsSelection.next().find('input').focus(function() {
                        thisObject.removeErrors();
                    });
                }
            });
        };
        this.modifyEvent = function() {
            var thisObject = this;
            var associationsList = this.associationsList;
            var associationEditorModal = this.associationEditorModal;
            var roleSelect = $(this.roleSelectId, associationEditorModal);

            // Adding modify association button click event.
            this.modifyBtn.click(function() {
                var tagsSelection = $('.form-autocomplete-selection', associationEditorModal);

                var isValid = thisObject.validate(tagsSelection);
                if (isValid) {
                    // Modifying row.
                    var thisRoleId = roleSelect.val();
                    var thisRoleName = roleSelect.find('option:selected').text();

                    var thisRow = $('.actions[data-roleid="' + thisRoleId + '"]').closest('tr');
                    var selectedRole = {id: thisRoleId, name: thisRoleName};
                    var selectedTags = $('.badge', tagsSelection);
                    associationsList.modifyRow(thisRow, selectedRole, selectedTags);

                    // Hide modal.
                    associationEditorModal.modal('hide');
                } else {
                    tagsSelection.next().find('input').focus(function() {
                        thisObject.removeErrors();
                    });
                }
            });
        };
        this.deleteEvent = function() {
            var thisObject = this;
            var associationsList = this.associationsList;

            // Adding delete association button click event.
            this.deleteBtn.click(function() {
                var thisRow = $(this).closest('tr');
                thisObject.updateRolesSelect(thisRow);
                associationsList.deleteRow(thisRow);
            });
        };
        this.updateRolesSelect = function(roleRow) {
            var optionVal = $('.actions', roleRow).attr('data-roleid');
            var optionText = $('.actions', roleRow).attr('data-rolename');
            $('<option/>').val(optionVal).html(optionText).appendTo(this.roleSelectId);
        };
        this.updateTagsSelect = function(data) {
            var thisObject = this;

            this.associationsList.empty();

            // Populate tags list.
            $(thisObject.tagsSelectId).empty();
            $.each(data, function(key, value) {
                $('<option/>').val(key).html(value).appendTo(thisObject.tagsSelectId);
            });

        };
        this.resetTags = function() {
            var tagsSelection = $('.form-autocomplete-selection', this.associationEditorModal);
            var selectedTags = $('.badge', tagsSelection);

            selectedTags.each(function() {
                var thisTagElement = $(this);
                thisTagElement.trigger('click');
            });
        };
        this.validate = function() {
            this.removeErrors();
            var selectionArea = $('.form-autocomplete-selection', this.associationEditorModal);
            if (!$('.badge', selectionArea).length) {
                $('.select-tag-error').removeClass('hidden');
                selectionArea.next().find('input').addClass('has-error');
                return false;
            }
            return true;
        };
        this.removeErrors = function() {
            $('.select-tag-error', this.associationEditorModal).addClass('hidden');
            $('.has-error', this.associationEditorModal).removeClass('has-error');
        };
    }

    /**
     * Wikifilter editor.
     */
    function WikifilterEditor() {
        this.wiki = $('#id_wiki');
        this.associations = $('#id_associations');
        this.associationsEditor = new AssociationsEditor();
        this.init = function() {
            this.wikiChangeEvent();
            this.associationsEditor.init();
        };
        this.wikiChangeEvent = function() {
            var thisObject = this;
            this.wiki.change(function() {
                var wikiID = $(this).val();
                ajax.call([{
                    methodname: 'wikifilter_external_getwikipagestags',
                    args: {
                        id:  wikiID
                    },
                }])[0].done(function(response) {
                    var data = JSON.parse(response);
                    thisObject.associationsEditor.updateTagsSelect(data);
                    return;
                }).fail(notification.exception);
            });
        };
    }

    return {
        init: function() {
            $(document).ready(function() {
                if ($('#id_wiki_header .alert-danger').length) {
                    // Disable action buttons if no wiki in course.
                    $('#id_submitbutton').attr('disabled', 'disabled');
                    $('#id_submitbutton2').attr('disabled', 'disabled');
                } else {
                    var wikifilter = new WikifilterEditor();
                    wikifilter.init();
                }
            });
        }
    };
});

