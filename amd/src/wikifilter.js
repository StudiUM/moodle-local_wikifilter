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
define([
    'jquery',
    'core/ajax',
    'core/form-autocomplete',
    'core/str', 'core/notification'
], function(
    $,
    ajax,
    autocomplete,
    str,
    notification
) {
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

                tagsCell += '<span class="badge badge-info mb-3 mr-1" data-id="' + thisTagId + '">' + thisTagText + '</span>';
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
     * Wikifilter association role select.
     */
     function AssociationRoleSelect() {
        this.id = '#id_role';
        this.element = $(this.id);
        this.showInEditMode = function(role) {
            this.addOption(role.id, role.name);
            this.setValue(role.id);
            this.disable();
        };
        this.showInAddMode = function(selectedRoles) {
            var thisObject = this;
            this.enable();
            $('.actions', selectedRoles).each(function() {
                var thisRoleId = $(this).attr('data-roleid');
                thisObject.deleteOption(thisRoleId);
            });
        };
        this.setValue = function(value) {
            this.element.val(value);
        };
        this.getSelectedOption = function() {
            return {id: this.element.val(), name: this.element.find('option:selected').text()};
        };
        this.deleteSelectedOption = function() {
            this.element.find('option:selected').remove();
        };
        this.addOption = function(key, value) {
            $('<option/>').val(key).remove().html(value).appendTo(this.id);
        };
        this.deleteOption = function(id) {
            this.element.find('option[value="' + id + '"]').remove();
        };
        this.enable = function() {
            this.element.prop("disabled", false);
        };
        this.disable = function() {
            this.element.prop("disabled", true);
        };
    }

    /**
     * Wikifilter association tags select.
     */
     function AssociationTagsSelect() {
        this.id = '#id_wikitags';
        this.element = $(this.id);
        this.showInEditMode = function(selectedTags) {
            var thisObject = this;
            selectedTags.each(function() {
                var thisTagId = $(this).attr('data-id');
                thisObject.selectOption(thisTagId);
            });

            this.buildAutocompleteComponent();
        };
        this.selectOption = function(id) {
            this.element.find('option[value="' + id + '"]').prop("selected", true);
        };
        this.unselectOptions = function() {
            this.element.find('option:selected').prop("selected", false);
        };
        this.buildAutocompleteComponent = function() {
            var thisObject = this;
            var stringkeys = [{key: 'search', component: 'wikifilter'}, {key: 'selectionarea', component: 'wikifilter'}];
            str.get_strings(stringkeys).then(function(langstrings) {
                var placeholder = langstrings[0];
                var noSelectionString = langstrings[1];

                thisObject.element.nextAll().remove();
                autocomplete.enhance(thisObject.id, false, '', placeholder, false, true, noSelectionString, true);
            }).fail(notification.exception);
        };
        this.loadOptions = function(data) {
            var thisObject = this;
            // Populate tags list.
            this.element.empty();
            $.each(data, function(key, value) {
                $('<option/>').val(key).html(value).appendTo(thisObject.id);
            });
        };
        this.reset = function() {
            this.unselectOptions();
            this.buildAutocompleteComponent();
        };
    }

    /**
     * Wikifilter associations editor.
     */
    function AssociationsEditor() {
        this.associationsList = new AssociationsList();
        this.associationRoleSelect = new AssociationRoleSelect();
        this.associationTagsSelect = new AssociationTagsSelect();
        this.associationEditorModal = $('#association-editor-modal');
        this.addTitle = $('.modal-title.add', this.associationEditorModal);
        this.editTitle = $('.modal-title.edit', this.associationEditorModal);
        this.addBtn = $('.add-btn', this.associationEditorModal);
        this.modifyBtn = $('.modify-btn', this.associationEditorModal);
        this.deleteBtn = $('.delete-icon');
        this.init = function() {
            this.events();
        };
        this.events = function() {
            // Show association editor modal event.
            this.showModalEvent();

            // Hide association editor modal event.
            this.hideModalEvent();

            // Add association event.
            this.addEvent();

            // Modify association event.
            this.modifyEvent();

            // Delete association event.
            this.deleteEvent();
        };
        this.showModalEvent = function() {
            var thisObject = this;
            this.associationEditorModal.on('show.bs.modal', function(e) {
                var callerBtn = $(e.relatedTarget);

                if (callerBtn.hasClass('edit')) { // Edit mode.
                    var thisRoleId = callerBtn.closest('.actions').attr('data-roleid');
                    var thisRoleName = callerBtn.closest('.actions').attr('data-rolename');

                    // Update modal header title and action buttons
                    thisObject.updateModalSections('edit');
                    thisObject.modifyBtn.attr('data-item', thisRoleId);

                    // Show role select in edit mode.
                    var role = {id: thisRoleId, name: thisRoleName};
                    thisObject.associationRoleSelect.showInEditMode(role);

                    // Show tags select in edit mode.
                    var thisRoleRow = callerBtn.closest('tr');
                    var thisRoleTags = $('.tags span', thisRoleRow);
                    thisObject.associationTagsSelect.showInEditMode(thisRoleTags);

                } else { // Add mode.
                    // Show role select in add mode.
                    thisObject.associationRoleSelect.showInAddMode(thisObject.associationsList.table);

                }
            });
        };
        this.hideModalEvent = function() {
            var thisObject = this;
            this.associationEditorModal.on('hidden.bs.modal', function() {
                // Update modal header title and action buttons
                thisObject.updateModalSections('add');

                thisObject.removeErrors();

                // Reset tags select.
                thisObject.associationTagsSelect.reset();
            });
        };
        this.updateModalSections = function(mode) {
            if (mode == 'edit') {
                this.addTitle.addClass('hidden');
                this.editTitle.removeClass('hidden');
                this.addBtn.addClass('hidden');
                this.modifyBtn.removeClass('hidden');
            } else {
                this.addTitle.removeClass('hidden');
                this.editTitle.addClass('hidden');
                this.addBtn.removeClass('hidden');
                this.modifyBtn.addClass('hidden');
            }

        };
        this.addEvent = function() {
            var thisObject = this;
            var associationsList = this.associationsList;
            var associationEditorModal = this.associationEditorModal;

            // Adding addassociation button click event.
            this.addBtn.click(function() {
                var tagsSelection = $('.form-autocomplete-selection', associationEditorModal);

                // Validate associations form.
                var isValid = thisObject.validate(tagsSelection);
                if (isValid) {
                    // Adding new row.
                    var selectedRole = thisObject.associationRoleSelect.getSelectedOption();
                    var selectedTags = $('.badge', tagsSelection);
                    associationsList.addRow(selectedRole, selectedTags);

                    // Adding new row delete association button click event.
                    $('tr:last .delete-icon', associationsList.table).click(function() {
                        var thisRow = $(this).closest('tr');
                        var thisRoleId = $('.actions', thisRow).attr('data-roleid');
                        var thisRoleName = $('.actions', thisRow).attr('data-rolename');
                        thisObject.associationRoleSelect.addOption(thisRoleId, thisRoleName);
                        associationsList.deleteRow(thisRow);
                    });

                    // Deleting selected role from roleSelect.
                    thisObject.associationRoleSelect.deleteSelectedOption();

                    // Showing modal.
                    associationEditorModal.modal('hide');

                } else {
                    // Tags selection input focus event.
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

            // Adding modify association button click event.
            this.modifyBtn.click(function() {
                var tagsSelection = $('.form-autocomplete-selection', associationEditorModal);

                var isValid = thisObject.validate(tagsSelection);
                if (isValid) {
                    // Modifying row.
                    var selectedRole = thisObject.associationRoleSelect.getSelectedOption();
                    var thisRow = $('.actions[data-roleid="' + selectedRole.id + '"]').closest('tr');
                    var selectedTags = $('.badge', tagsSelection);
                    associationsList.modifyRow(thisRow, selectedRole, selectedTags);

                    // Hiding modal.
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
                var thisRoleId = $('.actions', thisRow).attr('data-roleid');
                var thisRoleName = $('.actions', thisRow).attr('data-rolename');
                thisObject.associationRoleSelect.addOption(thisRoleId, thisRoleName);
                associationsList.deleteRow(thisRow);
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
                    thisObject.associationsEditor.associationsList.empty();
                    thisObject.associationsEditor.associationTagsSelect.loadOptions(data);
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

