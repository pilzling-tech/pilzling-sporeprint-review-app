#!/usr/bin/env python3
"""
Umlaut-Substitutions-Check fuer Git Pre-commit + Commit-msg Hooks.

Scant staged .md-Dateien (pre-commit) oder die Commit-Message (commit-msg)
gegen die Patterns in _tools/umlauts-patterns.txt. Treffer werden als
file:line:word ausgegeben und blockieren den Commit.

Nutzung:
    python _tools/check_umlauts.py --staged-md      # pre-commit Mode
    python _tools/check_umlauts.py --commit-msg <file>  # commit-msg Mode

Bypass im Notfall: git commit --no-verify (nicht missbrauchen).
"""
import os
import re
import subprocess
import sys
from pathlib import Path

# Windows-Konsole zwingen UTF-8 auszugeben (Pfeile, Umlaute in Hinweis-Texten)
try:
    sys.stdout.reconfigure(encoding="utf-8")
    sys.stderr.reconfigure(encoding="utf-8")
except Exception:
    pass

ROOT = Path(__file__).resolve().parent.parent
PATTERNS_FILE = ROOT / "_tools" / "umlauts-patterns.txt"
ALLOWLIST_FILE = ROOT / "_tools" / "umlauts-allowlist.txt"

# Dateien/Pfade die nie gescant werden (Archive, Vendor, Daten-Dumps)
EXCLUDE_PREFIXES = (
    "_archive/",
    "_archive\\",
    "vendor/",
    "node_modules/",
    "references/",
    "_db/",
)


def load_patterns():
    if not PATTERNS_FILE.exists():
        print(f"WARN: {PATTERNS_FILE} fehlt — kein Umlaut-Check moeglich")
        return []
    pats = []
    for line in PATTERNS_FILE.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#"):
            continue
        # Word-boundary + case-insensitive
        pats.append(re.compile(r"\b" + line + r"\b", re.IGNORECASE))
    return pats


def load_allowlist():
    if not ALLOWLIST_FILE.exists():
        return set()
    words = set()
    for line in ALLOWLIST_FILE.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#"):
            continue
        words.add(line)
    return words


def is_allowed(matched_word, allowlist):
    # Case-sensitive Match gegen Allowlist (Eigennamen sind case-sensitive)
    return matched_word in allowlist


def check_text(text, patterns, allowlist, source_label):
    """Scan text fuer Umlaut-Substitutionen. Gibt Liste von Hits zurueck."""
    hits = []
    has_umlaut = re.compile(r"[äöüÄÖÜß]")
    for lineno, line in enumerate(text.splitlines(), start=1):
        stripped = line.strip()
        # Markdown-Tabellen-Zeilen (|...|) ueberspringen — dort wird typisch
        # die Verbots-Liste selbst dokumentiert (Falsch | Richtig).
        if stripped.startswith("|"):
            continue
        # Zeilen die SOWOHL eine ASCII-Substitution ALS AUCH einen echten Umlaut
        # enthalten sind fast immer Doku-Erklaerungen ("'fuer' → 'für'") und
        # werden uebersprungen.
        line_has_umlaut = bool(has_umlaut.search(line))
        for pat in patterns:
            for m in pat.finditer(line):
                word = m.group(0)
                if is_allowed(word, allowlist):
                    continue
                if line_has_umlaut:
                    continue
                hits.append((source_label, lineno, word, stripped))
    return hits


def get_staged_md_files():
    """Listet staged .md-Dateien (Added oder Modified)."""
    try:
        out = subprocess.check_output(
            ["git", "diff", "--cached", "--name-only", "--diff-filter=AM"],
            cwd=ROOT,
            text=True,
        )
    except subprocess.CalledProcessError:
        return []
    files = []
    for f in out.splitlines():
        f = f.strip()
        if not f.endswith(".md"):
            continue
        if any(f.startswith(p) for p in EXCLUDE_PREFIXES):
            continue
        files.append(f)
    return files


def get_staged_added_lines(filepath):
    """
    Liest die HINZUGEFUEGTEN Zeilen einer staged Datei (nicht das ganze File).
    So wird die existierende Doku-Schuld nicht jedes Mal geblockt — nur Neues.
    Liefert Liste (lineno_in_new_file, line_content).
    """
    try:
        out = subprocess.check_output(
            ["git", "diff", "--cached", "--unified=0", "--no-color", "--", filepath],
            cwd=ROOT,
            text=True,
            encoding="utf-8",
            errors="replace",
        )
    except subprocess.CalledProcessError:
        return []
    added = []
    current_lineno = 0
    for line in out.splitlines():
        if line.startswith("@@"):
            # Hunk-Header parsen: @@ -alt +neu_start[,neu_count] @@
            m = re.search(r"\+(\d+)(?:,(\d+))?", line)
            if m:
                current_lineno = int(m.group(1))
            continue
        if line.startswith("+++") or line.startswith("---"):
            continue
        if line.startswith("+"):
            # Tabellen-Definitions-Zeile in CLAUDE.md selbst nicht scannen
            content = line[1:]
            # Skip wenn Zeile aussieht wie Tabellen-Eintrag der Verbots-Liste
            # ("| fuer | für |" Format)
            if re.match(r"^\s*\|\s*[a-zA-Z]+\s*(,\s*[a-zA-Z]+\s*)*\|", content):
                current_lineno += 1
                continue
            added.append((current_lineno, content))
            current_lineno += 1
        elif line.startswith("-"):
            # Removed line, kein Lineno-Increment im neuen File
            pass
        else:
            current_lineno += 1
    return added


def main():
    if len(sys.argv) < 2:
        print("Nutzung: check_umlauts.py --staged-md | --commit-msg <file>")
        sys.exit(2)

    patterns = load_patterns()
    if not patterns:
        sys.exit(0)
    allowlist = load_allowlist()

    mode = sys.argv[1]
    all_hits = []

    if mode == "--staged-md":
        for fp in get_staged_md_files():
            for lineno, content in get_staged_added_lines(fp):
                hits = check_text(content, patterns, allowlist, fp)
                # check_text gibt lineno=1 zurueck (eine Zeile) — auf echte Zeile mappen
                for (_src, _ln, word, line_strip) in hits:
                    all_hits.append((fp, lineno, word, line_strip))
    elif mode == "--commit-msg":
        if len(sys.argv) < 3:
            print("Nutzung: check_umlauts.py --commit-msg <messagefile>")
            sys.exit(2)
        msg_file = sys.argv[2]
        try:
            text = Path(msg_file).read_text(encoding="utf-8", errors="replace")
        except Exception as e:
            print(f"Konnte Commit-Message nicht lesen: {e}")
            sys.exit(0)
        # Ignoriere Kommentar-Zeilen die mit # beginnen (Git-default)
        clean = "\n".join(l for l in text.splitlines() if not l.lstrip().startswith("#"))
        hits = check_text(clean, patterns, allowlist, "<commit-msg>")
        all_hits.extend(hits)
    else:
        print(f"Unbekannter Mode: {mode}")
        sys.exit(2)

    if not all_hits:
        sys.exit(0)

    print()
    print("=" * 70)
    print("UMLAUT-CHECK FEHLGESCHLAGEN — bitte ASCII-Substitutionen ersetzen:")
    print("=" * 70)
    for hit in all_hits:
        fp, lineno, word, line_strip = hit
        print(f"  {fp}:{lineno}  →  '{word}'")
        print(f"    {line_strip[:120]}")
    print()
    print("Fix:  ersetze die Wörter mit korrekten Umlauten (siehe CLAUDE.md Verbots-Liste)")
    print("Bypass im Notfall: git commit --no-verify  (NICHT missbrauchen)")
    print()
    sys.exit(1)


if __name__ == "__main__":
    main()
