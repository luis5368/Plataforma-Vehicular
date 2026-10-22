// Reports paths and finding types only. Never reads an untracked .env or prints matches.
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const files = execFileSync('git', ['ls-files', '-z'], { encoding: 'utf8' }).split('\0').filter(Boolean);
const findings = [];
for (const path of files) {
    const types = new Set();
    if (/(^|\/)\.env(?:\.|$)/.test(path) && !/\.example$/.test(path)) types.add('environment-file');
    if (/(?:^|\/)(?:auth\.json|id_rsa|id_ed25519)$|\.(?:pem|p12|pfx|sql|sql\.gz)$/i.test(path)) types.add('credential-or-database-artifact-review');
    if (!fs.existsSync(path) || fs.statSync(path).size > 5_000_000) continue;
    const source = fs.readFileSync(path, 'utf8');
    if (/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/.test(source)) types.add('private-key-material');
    if (/(?:GOCSPX-[\w-]{12,}|AKIA[0-9A-Z]{16}|gh[pousr]_[A-Za-z0-9]{30,})/.test(source)) types.add('provider-credential-pattern');
    if (/^\s*APP_KEY\s*=\s*["']?base64:[A-Za-z0-9+/]{40,}/m.test(source)) types.add('application-key-assignment');
    if (/^\s*(?:MAIL_PASSWORD|GOOGLE_CLIENT_SECRET|DB_PASSWORD)\s*=\s*["']?[^\s"'#]+/m.test(source) && !/\.example$|\.md$/.test(path)) types.add('credential-assignment-review');
    if (/Hash::check\(\s*['"][^'"]+['"]\s*,/.test(source) && path.includes('migrations/')) types.add('legacy-hardcoded-password-comparison');
    if (types.size) findings.push({ file: path, types: [...types] });
}
process.stdout.write(JSON.stringify({ tracked_files: files.length, findings, scope: 'current-index-paths-working-tree-content; heuristic; not-history-or-provider-validation' }, null, 2) + '\n');
