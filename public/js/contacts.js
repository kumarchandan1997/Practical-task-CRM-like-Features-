$(document).ready(function () {

    $('#search').on('keyup', function () {
        let value = $(this).val();
    
        $.get('/contacts/search', { search: value }, function (data) {
            if (!data.length) {
                $('#contactTable').html('<tr><td colspan="100%">No contacts found</td></tr>');
                return;
            }
    
            let allCustomFieldNames = new Set();
            data.forEach(contact => {
                contact.custom_fields.forEach(field => allCustomFieldNames.add(field.field_name));
            });
    
            let customFieldsArray = Array.from(allCustomFieldNames);
    
            // Generate table headers dynamically
            let headers = `
                <tr>
                    <th></th> <!-- Checkbox Column -->
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    ${customFieldsArray.map(field => `<th>${field}</th>`).join('')}
                    <th>Actions</th>
                </tr>
            `;
            $('.table thead').html(headers);
    
            let tableContent = data.map(contact => {
                let customFields = customFieldsArray.map(fieldName => {
                    let field = contact.custom_fields.find(f => f.field_name === fieldName);
                    return `<td>${field ? field.field_value : '-'}</td>`;
                }).join('');
    
                return `
                    <tr id="contactRow_${contact.id}">
                        <td>
                            <input type="checkbox" class="contactCheckbox" value="${contact.id}">
                        </td>
                        <td>${contact.name}</td>
                        <td>${contact.email}</td>
                        <td>${contact.phone}</td>
                        <td>${contact.gender}</td>
                        ${customFields}
                        <td>
                            <a href="/contacts/edit/${contact.id}" class="btn btn-primary btn-sm">Edit</a>
                            <button class="btn btn-danger deleteBtn" data-id="${contact.id}">Delete</button>
                        </td>
                    </tr>
                `;
            }).join('');
    
            $('#contactTable').html(tableContent);
        });
    });
    
    
    $(document).on('click', '.deleteBtn', function () {
        let id = $(this).data('id');
        let csrfToken = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: `/contacts/delete/${id}`,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                $(`#contactRow_${id}`).remove();
                $('#successMessage').text('Contact deleted successfully!').fadeIn().delay(2000).fadeOut();
            },
            error: function (xhr) {
                alert("Error deleting contact: " + xhr.responseText);
            }
        });
    });

    $('#resetSearch').click(function () {
        $('#search').val('');
        $('#contactTable tr').show();
        location.reload();
    });

    $('#mergeContacts').click(function () {
        let selectedContactIds = [];

        $('.contactCheckbox:checked').each(function () {
            selectedContactIds.push($(this).val());
        });

        if (selectedContactIds.length < 2) {
            showAlert('warning', 'Invalid Selection', 'Please select at least two contacts to merge!');
            return;
        }

        $.ajax({
            url: '/contacts/get-names',
            method: 'POST',
            data: {
                ids: selectedContactIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    let contactListHtml = "";
                    response.contactNames.forEach(function (contact) {
                        contactListHtml += `<li><input type="checkbox" class="mergeCheckbox" value="${contact.id}"> ${contact.name}</li>`;
                    });
                    $('#selectedContactsList').html(contactListHtml);
                    $('#mergeModal').modal('show');
                } else {
                    alert("An error occurred while fetching contact names.");
                }
            },
            error: function () {
                alert("An error occurred during the AJAX request.");
            }
        });
    });

    $('#confirmMerge').click(function () {
        let selectedParent = $('.mergeCheckbox:checked').map(function () {
            return $(this).val();
        }).get();
    
        if (selectedParent.length === 0) {
            showAlert('warning', 'Invalid Selection', 'Please select a Master Contact!');
            return;
        }
    
        if (selectedParent.length > 1) {
            showAlert('warning', 'Invalid Selection', 'Only one Master Contact can be selected!');
            return;
        }
    
        let allContacts = [];
        $('.mergeCheckbox').each(function () {
            allContacts.push($(this).val());
        });
    
        showConfirmationDialog('Are you sure?', 'Do you really want to merge the selected contacts?', 'Yes, Merge!', function () {
            $.ajax({
                url: '/merge-contacts',
                method: 'POST',
                data: {
                    parentId: selectedParent[0], // Select only one as Master Contact
                    contactIds: allContacts,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    showAlert('success', 'Merged!', response.success, function () {
                        $('#mergeModal').modal('hide');
                        location.reload();
                    });
                },
                error: function (xhr) {
                    showAlert('error', 'Merge Failed!', xhr.responseJSON.error || 'An error occurred while merging contacts.');
                }
            });
        });
    });
    

    $('#addCustomField').click(function () {
        let fieldIndex = $('.custom-field').length;
        let fieldHtml = `
            <div class="row mb-3 custom-field">
                <div class="col-md-5">
                    <input type="text" name="custom_fields[${fieldIndex}][field_name]" class="form-control" placeholder="Field Name" required>
                </div>
                <div class="col-md-5">
                    <input type="text" name="custom_fields[${fieldIndex}][field_value]" class="form-control" placeholder="Field Value" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger removeField">Remove</button>
                </div>
            </div>`;
        $('#customFieldsContainer').append(fieldHtml);
    });

    $(document).on('click', '.removeField', function () {
        $(this).closest('.custom-field').remove();
    });
});


function showConfirmationDialog(title, text, confirmButtonText, callback) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmButtonText,
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

function showAlert(type, title, text, callback = null) {
    Swal.fire({
        icon: type,
        title: title,
        text: text,
    }).then(() => {
        if (typeof callback === 'function') {
            callback();
        }
    });
}
