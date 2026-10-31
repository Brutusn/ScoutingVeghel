#!/usr/bin/env python3
"""Check tracked source before pushing. Never print a detected secret value."""
from pathlib import Path
import re
import subprocess
import sys

root = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else Path(__file__).resolve().parents[1]
paths = subprocess.check_output(['git', '-C', str(root), 'ls-files', '-z']).decode().split('\0')
allowed_public_contacts = {'inveiligehanden@scouting.nl', 'socialeveiligheid@scouting.nl'}
email = re.compile(r'[A-Za-z0-9.!#$%&*+/=?^_`{|}~-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}')
credentials = re.compile(r'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----|\b(?:ghp_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{30,}|AKIA[A-Z0-9]{16})')
literal_secret = re.compile(r"\$(?:secret|SMTP_PASSWORD|DB_PASSWORD)\s*=\s*(['\"])([^'\"]{8,})\1")
array_secret = re.compile(r"['\"](?:password|recaptcha_secret|api_key)['\"]\s*=>\s*(['\"])([^'\"]{8,})\1")
issues = []
for name in filter(None, paths):
    path = root / name
    if not path.is_file():  # Submodules remain pinned, separate repositories.
        continue
    if set(Path(name).parts) & {'private', 'local', '.svn', '.vscode', 'privateSettings', 'mail', 'backups', 'exports'} or path.name in {'sftp.json', 'client.cnf', 'DB2.php', 'MAIL2.php'}:
        issues.append((name, 0, 'private/runtimebestand'))
    data = path.read_bytes()
    if b'\0' in data or path.suffix.lower() in {'.pdf', '.docx', '.png', '.jpg', '.jpeg', '.webp', '.mp4', '.ico', '.woff', '.woff2', '.ttf', '.eot'}:
        continue
    try:
        source = data.decode('utf-8')
    except UnicodeDecodeError:
        issues.append((name, 0, 'onverwachte tekstcodering'))
        continue
    for number, line in enumerate(source.splitlines(), 1):
        if any(not value.endswith('.invalid') and value not in allowed_public_contacts for value in email.findall(line)):
            issues.append((name, number, 'niet-fictief intern e-mailadres'))
        match = array_secret.search(line)
        array_value_is_secret = bool(match and match.group(2) not in {'CHANGE_ME', 'YOUR_SECRET_HERE'})
        if credentials.search(line) or literal_secret.search(line) or array_value_is_secret:
            issues.append((name, number, 'mogelijk hardgecodeerd geheim'))
for name, line, reason in issues:
    print(f'{name}:{line}: {reason}; waarde weggelaten')
if issues:
    raise SystemExit(1)
print('Tracked source: no private/runtime files, internal recipients or credential patterns found')
