from pathlib import Path
import json
import time

from config.config import WHITELIST_LIMIT


class AccountManager:
    def __init__(self):
        try:
            path = Path("data/")
            path.mkdir(parents=True)
        except:
            print("That path exists already, you dumbfuck.")
        path = Path("data/whitelist.json")
        if not path.exists():
            with path.open("w") as f:
                json.dump([], f)

    def _write(self, data: object):
        path = Path("data/whitelist.json")
        with path.open('w') as f:
            return json.dump(data, f)

    def _get_accounts(self):
        path = Path("data/whitelist.json")
        with path.open() as f:
            return json.load(f)

    def _is_whitelisted(self, username: str):
        users = self._get_accounts()
        for user in users:
            if user["name"] == username:
                return True
        return False

    def _count_whitelisted(self, discord_id: int):
        users = self._get_accounts()
        count = 0
        for user in users:
            if user["uid"] == discord_id:
                count += 1
        return count

    def whitelist_add(self, username: str, discord_id: int, override: bool = False):
        if self._count_whitelisted(discord_id) >= WHITELIST_LIMIT and not override:
            return False, f"You cannot whitelist any more users! ({WHITELIST_LIMIT})"
        if self._is_whitelisted(username):
            return False, "Account is already whitelisted."
        data = self._get_accounts()
        data.append({
            "name": username,
            "uid": discord_id
        })
        self._write(data)
        return True, f"Successfully added {username} ({discord_id}) to the whitelist database."

    def whitelist_remove(self, username: str):
        if not self._is_whitelisted(username):
            return False, "This account isn't in the whitelist database."
        data = self._get_accounts()
        for i, item in enumerate(data):
            if item["name"] == username:
                data.pop(i)
        self._write(data)
        return True, f"User {username} was removed from the whitelist database."

    def count_whitelisted(self):
        return len(self._get_accounts())