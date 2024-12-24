class ChatManager {
    constructor() {
        this.chatContainer = document.getElementById('chat-messages');
        this.messageForm = document.getElementById('message-form');
        this.messageInput = this.messageForm.querySelector('[name="message"]');
        this.attachmentInput = document.getElementById('attachment');
        this.lastMessageId = 0;
        
        this.init();
    }

    init() {
        this.setupFormHandler();
        this.setupFileHandler();
        this.startAutoRefresh();
        this.setupScrollHandler();
    }

    startAutoRefresh() {
        this.loadMessages();
        setInterval(() => this.loadMessages(), 3000);
    }

    setupScrollHandler() {
        this.chatContainer.addEventListener('scroll', () => {
            const isNearBottom = this.chatContainer.scrollHeight - this.chatContainer.scrollTop 
                               <= this.chatContainer.clientHeight + 100;
            if (isNearBottom) {
                this.shouldScroll = true;
            } else {
                this.shouldScroll = false;
            }
        });
    }

    async loadMessages() {
        try {
            const jobId = this.messageForm.querySelector('[name="job_id"]').value;
            const response = await fetch(`api/get_messages.php?job_id=${jobId}`);
            const messages = await response.json();

            if (!messages.error) {
                this.renderMessages(messages);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    renderMessages(messages) {
        let shouldScroll = this.shouldAutoScroll();
        let fragment = document.createDocumentFragment();
        let newMessages = false;

        messages.forEach(message => {
            if (message.id > this.lastMessageId) {
                const messageElement = this.createMessageElement(message);
                fragment.appendChild(messageElement);
                this.lastMessageId = message.id;
                newMessages = true;
            }
        });

        if (newMessages) {
            this.chatContainer.appendChild(fragment);
            if (shouldScroll) {
                this.scrollToBottom();
            }
        }
    }

    createMessageElement(message) {
        const senderId = this.messageForm.querySelector('[name="sender_id"]').value;
        const isSent = message.sender_id == senderId;
        
        const div = document.createElement('div');
        div.className = `message-bubble ${isSent ? 'message-sent' : 'message-received'}`;
        
        let content = `<div class="message-content">${this.escapeHtml(message.message)}</div>`;
        
        if (message.attachment_path) {
            content += this.createAttachmentPreview(message.attachment_path);
        }
        
        content += `
            <div class="message-time">
                ${message.sender_name} • ${new Date(message.created_at).toLocaleTimeString()}
            </div>
        `;

        div.innerHTML = content;
        return div;
    }

    createAttachmentPreview(path) {
        const ext = path.split('.').pop().toLowerCase();
        const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);

        if (isImage) {
            return `<div class="attachment-preview">
                <img src="${path}" alt="Attachment" style="max-width: 200px; margin-top: 10px;">
            </div>`;
        }

        return `<div class="attachment-preview">
            <a href="${path}" target="_blank" class="btn btn-sm btn-secondary mt-2">
                <i class="fas fa-download"></i> Download Attachment
            </a>
        </div>`;
    }

    setupFormHandler() {
        this.messageForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const formData = new FormData(this.messageForm);
                const response = await fetch('api/send_message.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                this.messageForm.reset();
                await this.loadMessages();
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message: ' + error.message);
            }
        });
    }

    setupFileHandler() {
        const fileInput = this.messageForm.querySelector('#attachment');
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (file) {
                if (file.size > 5242880) {
                    alert('File is too large. Maximum size is 5MB');
                    fileInput.value = '';
                }
            }
        });
    }

    shouldAutoScroll() {
        const threshold = 100;
        const position = this.chatContainer.scrollHeight - 
                        (this.chatContainer.scrollTop + this.chatContainer.clientHeight);
        return position <= threshold;
    }

    scrollToBottom() {
        this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
    }

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Initialize chat when DOM is loaded
document.addEventListener('DOMContentLoaded', () => new ChatManager());