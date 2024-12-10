
<a data-target="#emailMessageModal"
   data-voucher-id="{{ $row->email_id }}"
   data-email="{{ $row->email }}" 
   data-message="{{ $row->message }}" 
   class="btn btn-info btn-sm view-btn ms-2">
   Message
</a>

<div class="modal fade" id="emailMessageModal" tabindex="-1" aria-labelledby="emailMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailMessageModalLabel">Email Message</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- <p><strong>Email:</strong> <span id="modal-email"></span></p> --}}
                <p><strong>Message:</strong></p>
                <p id="modal-message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        // Listen for the click event on the "Message" button
        $(document).on('click', '.view-btn', function () {
            // Retrieve the message data attribute from the clicked button
            const message = $(this).data('message');

            // Decode HTML entities and replace newline characters with <br>
            const decodedMessage = message.replace(/\\r/g, '\n').replace(/\\n/g, "<br>");

            // Set the message in the modal
            $('#modal-message').html(decodedMessage);

            // Show the modal
            $('#emailMessageModal').modal('show');
        });
    });
</script>



