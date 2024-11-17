# Fixtures

Fixtures for all types of Telegram Updates.
Please organize them by names with a clear structure.

They should belong to the same chat and user, so you can easily test the interaction between them.

## Private chat

By default, please assume it's a chat between your bot and a user with the following data:
```json
{
    "id": 4444,
    "is_bot": false,
    "first_name": "Firstname",
    "last_name": "Lastname",
    "username": "my_username",
    "language_code": "en"
}
```

Your bot data:
```json
{
    "id": 807,
    "is_bot": true,
    "first_name": "My Bot",
    "username": "MyBot"
}
```

## Group chat "Bots party"

Emulate a group chat activity for a chat with few bots.
```json
{
    "chat": {
        "id": -77777777,
        "title": "Bots party",
        "type": "group",
        "all_members_are_administrators": true
    }
}
```
