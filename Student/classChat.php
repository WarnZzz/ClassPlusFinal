<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_GET['classId'])) {
    header('Location: dashboard.php');
    exit();
}

$classId = intval($_GET['classId']);
$userId = $_SESSION['userId'];
$userRole = $_SESSION['userRole']; // 'student' or 'teacher'

// Fetch class details
$query = "SELECT Program, `Year(Batch)`, section FROM tblclass WHERE Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $classId);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class) {
    echo "Invalid class.";
    exit();
}

$className = $class['Program'] . " " . $class['Year(Batch)'] . "-" . $class['section'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Chat - <?php echo htmlspecialchars($className); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="../img/logo/attnlg.jpg" rel="icon">
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .chat-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 70vh;
            padding: 0;
        }
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            border-bottom: 1px solid #ddd;
        }
        .message {
            margin-bottom: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            max-width: 70%;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        .message.self { background: #d1e7dd; margin-left: auto; }
        .message.other { background: #f1f1f1; margin-right: auto; }
        .sender-label {
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .chat-input {
            display: flex;
            flex-direction: column;
            border-top: 1px solid #ddd;
            padding: 10px;
        }
        .chat-input textarea {
            resize: none;
            border-radius: 8px;
            padding: 8px;
            margin-bottom: 8px;
        }
        .chat-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .chat-actions label {
            cursor: pointer;
            margin-bottom: 0;
        }
        #file {
            display: none;
        }
        .file-preview {
            margin-top: 5px;
            font-size: 0.9rem;
            color: #555;
        }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include "Includes/sidebar.php"; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include "Includes/topbar.php"; ?>
            <div class="container-fluid" id="container-wrapper">
                <h1 class="h3 mb-4 text-gray-800">
                    Chatroom - <?php echo htmlspecialchars($className); ?>
                </h1>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-comments"></i> Class Chat
                    </div>
                    <div class="card-body">
                        <div class="chat-container">
                            <div id="chatMessages" class="chat-messages"></div>
                            <form id="chatForm" class="chat-input" enctype="multipart/form-data">
                                <textarea name="message" id="message" rows="2" placeholder="Type your message"></textarea>
                                <div class="chat-actions">
                                    <label for="file"><i class="fas fa-paperclip"></i></label>
                                    <input type="file" name="file" id="file">
                                    <button type="button" id="emoji-btn" class="btn btn-light"><i class="far fa-smile"></i></button>
                                    <button type="submit" class="btn btn-primary">Send</button>
                                </div>
                                <div id="file-preview" class="file-preview"></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include "Includes/footer.php"; ?>
        </div>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/ruang-admin.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@joeattardi/emoji-button@4.6.4/dist/emoji-button.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const classId = <?php echo $classId; ?>;
    const userId = <?php echo $userId; ?>;
    const userRole = "<?php echo $userRole; ?>";

    function renderMessages(messages) {
        const container = $('#chatMessages');
        container.empty();
        messages.forEach(msg => {
            const isSelf = msg.SenderId == userId;
            const messageDiv = $('<div>').addClass('message').addClass(isSelf ? 'self' : 'other');

            const senderLabel = $('<div>').addClass('sender-label').text(msg.SenderName + ' (' + msg.SenderRole + ')');
            messageDiv.append(senderLabel);

            if (msg.MessageText) {
                const textDiv = $('<div>').text(msg.MessageText);
                messageDiv.append(textDiv);
            }

            if (msg.FilePath) {
                const fileLink = $('<a>')
                    .attr('href', '../' + msg.FilePath)
                    .attr('target', '_blank')
                    .text('[Attachment]');
                messageDiv.append('<br>').append(fileLink);
            }

            container.append(messageDiv);
        });
        container.scrollTop(container[0].scrollHeight);
    }

    function fetchMessages() {
        $.getJSON('getClassMessages.php', { classId: classId })
            .done(data => {
                renderMessages(data);
            })
            .fail((jqxhr, textStatus, error) => {
                console.error("Failed to fetch messages:", textStatus, error, jqxhr.responseText);
            });
    }

    $('#chatForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('classId', classId);
        formData.append('senderId', userId);
        formData.append('senderRole', userRole);

        $.ajax({
            url: 'sendClassMessage.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.success) {
                    $('#message').val('');
                    $('#file').val('');
                    $('#file-preview').text('');
                    fetchMessages();
                } else if(response.error) {
                    alert("Error: " + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
                alert("Error sending message. See console.");
            }
        });
    });

    setInterval(fetchMessages, 2000);
    fetchMessages();

    const picker = new EmojiButton();
    const trigger = document.querySelector('#emoji-btn');
    const messageInput = document.querySelector('#message');

    picker.on('emoji', emoji => {
        messageInput.value += emoji.unicode || emoji.emoji || emoji.native || '';
        messageInput.focus();
    });

    trigger.addEventListener('click', () => picker.togglePicker(trigger));

    // Updated file preview with icon
    document.getElementById('file').addEventListener('change', function() {
        const file = this.files[0];
        const preview = document.getElementById('file-preview');
        if (file) {
            preview.innerHTML = `<i class="fas fa-paperclip"></i> ${file.name} (${Math.round(file.size / 1024)} KB)`;
        } else {
            preview.textContent = '';
        }
    });
});
</script>
</body>
</html>
