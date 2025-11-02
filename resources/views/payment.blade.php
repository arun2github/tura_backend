<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <script type="text/javascript">
        $(document).ready(function() {
            // Create the form dynamically
            var form = $('<form>', {
                'method': 'post',
                'action': 'https://www.sbiepay.sbi/secure/AggregatorHostedListener',
                'id': 'submitForm'
            });

            // Add the hidden input fields with the necessary data
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'EncryptTrans',
                'value': "{{ $encryptedData }}" // Dynamic encrypted data
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'merchIdVal',
                'value': '1003253'
            }));

            // Append the form to the body
            $('body').append(form);

            // Automatically submit the form
            form.submit(); // Trigger the form submission
        });
    </script>
</body>
</html>
