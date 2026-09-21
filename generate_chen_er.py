import matplotlib.pyplot as plt
import matplotlib.patches as patches
import os

# Set up figure
fig, ax = plt.subplots(figsize=(20, 13), dpi=300)
ax.set_xlim(0, 100)
ax.set_ylim(0, 100)
ax.axis("off")

# Colors matching user reference image
c_entity = "#2b5cb8"      # Rich royal blue for entities
c_rel = "#b91c1c"         # Deep crimson red for relationship diamonds
c_attr = "#dcfce7"        # Light mint green for attributes
c_attr_key = "#fef08a"    # Light gold/yellow for primary key attributes
c_attr_border = "#16a34a" # Dark green border
c_attr_key_border = "#ca8a04"
c_text_dark = "#0f172a"
c_line = "#334155"
c_card_text = "#b91c1c"   # Red cardinality text

def draw_entity(x, y, w, h, name):
    rect = patches.FancyBboxPatch(
        (x - w/2, y - h/2), w, h,
        boxstyle="square,pad=0",
        facecolor=c_entity, edgecolor="#1e3a8a", linewidth=2.2, zorder=3
    )
    ax.add_patch(rect)
    ax.text(x, y, name, color="white", fontsize=11, fontweight="bold",
            ha="center", va="center", zorder=4, family="sans-serif")

def draw_diamond(x, y, w, h, name):
    pts = [[x, y + h/2], [x + w/2, y], [x, y - h/2], [x - w/2, y]]
    poly = patches.Polygon(pts, facecolor=c_rel, edgecolor="#7f1d1d", linewidth=2, zorder=3)
    ax.add_patch(poly)
    ax.text(x, y, name, color="white", fontsize=9.5, fontweight="bold", fontstyle="italic",
            ha="center", va="center", zorder=4, family="sans-serif")

def draw_attribute(x, y, w, h, name, is_pk=False):
    fc = c_attr_key if is_pk else c_attr
    ec = c_attr_key_border if is_pk else c_attr_border
    lw = 2 if is_pk else 1.5
    
    ellipse = patches.Ellipse((x, y), w, h, facecolor=fc, edgecolor=ec, linewidth=lw, zorder=3)
    ax.add_patch(ellipse)
    
    if is_pk:
        ax.text(x, y + 0.2, name, color=c_text_dark, fontsize=8.5, fontweight="bold",
                ha="center", va="center", zorder=4, family="sans-serif")
        # Draw clean underline
        tw = len(name) * 0.40
        ax.plot([x - tw, x + tw], [y - h*0.24, y - h*0.24], color=c_text_dark, lw=1.3, zorder=4)
    else:
        ax.text(x, y, name, color=c_text_dark, fontsize=8, ha="center", va="center", zorder=4, family="sans-serif")

def connect(p1, p2, color=c_line, lw=1.3, z=2):
    ax.plot([p1[0], p2[0]], [p1[1], p2[1]], color=color, linewidth=lw, zorder=z)

def draw_cardinality(x, y, text):
    ax.text(x, y, text, color=c_card_text, fontsize=11, fontweight="bold",
            ha="center", va="center", zorder=5, family="sans-serif")

# ==============================================================================
# ENTITIES POSITIONS
# ==============================================================================
dept_pos = (20, 85)
draw_entity(dept_pos[0], dept_pos[1], 15, 6, "DEPARTMENT")

stud_pos = (20, 55)
draw_entity(stud_pos[0], stud_pos[1], 15, 6, "STUDENT")

reg_pos = (50, 55)
draw_entity(reg_pos[0], reg_pos[1], 15, 6, "REGISTRATION")

event_pos = (80, 55)
draw_entity(event_pos[0], event_pos[1], 15, 6, "EVENT")

venue_pos = (80, 85)
draw_entity(venue_pos[0], venue_pos[1], 15, 6, "VENUE")

cat_pos = (80, 20)
draw_entity(cat_pos[0], cat_pos[1], 15, 6, "CATEGORY")

admin_pos = (20, 20)
draw_entity(admin_pos[0], admin_pos[1], 15, 6, "ADMIN")


# ==============================================================================
# RELATIONSHIP DIAMONDS & CONNECTIONS
# ==============================================================================

# R1: enrolled_in (DEPARTMENT 1 --- M STUDENT)
r1_pos = (20, 70)
connect(dept_pos, r1_pos)
connect(r1_pos, stud_pos)
draw_diamond(r1_pos[0], r1_pos[1], 11, 5.5, "enrolled_in")
draw_cardinality(22.5, 78, "1")
draw_cardinality(22.5, 62, "1..*")

# R2: books / submits (STUDENT 1 --- M REGISTRATION)
r2_pos = (35, 55)
connect(stud_pos, r2_pos)
connect(r2_pos, reg_pos)
draw_diamond(r2_pos[0], r2_pos[1], 9, 5, "books")
draw_cardinality(29.5, 57.5, "1")
draw_cardinality(40.5, 57.5, "1..*")

# R3: for_event (REGISTRATION M --- 1 EVENT)
r3_pos = (65, 55)
connect(reg_pos, r3_pos)
connect(r3_pos, event_pos)
draw_diamond(r3_pos[0], r3_pos[1], 9, 5, "for_event")
draw_cardinality(59.5, 57.5, "1..*")
draw_cardinality(70.5, 57.5, "1")

# R4: hosted_at (VENUE 1 --- M EVENT)
r4_pos = (80, 70)
connect(venue_pos, r4_pos)
connect(r4_pos, event_pos)
draw_diamond(r4_pos[0], r4_pos[1], 9, 5, "hosted_at")
draw_cardinality(82.5, 78, "1")
draw_cardinality(82.5, 62, "1..*")

# R5: tagged_as (EVENT M --- M CATEGORY)
r5_pos = (80, 37.5)
connect(event_pos, r5_pos)
connect(r5_pos, cat_pos)
draw_diamond(r5_pos[0], r5_pos[1], 10, 5.5, "tagged_as")
draw_cardinality(82.5, 46.5, "1..*")
draw_cardinality(82.5, 29, "1..*")

# R6: manages (ADMIN 1 --- M EVENT)
r6_pos = (50, 20)
connect(admin_pos, r6_pos)
connect(r6_pos, (50, 33))
connect((50, 33), (72.5, 43))
connect((72.5, 43), (76, 52))
draw_diamond(r6_pos[0], r6_pos[1], 9, 5, "manages")
draw_cardinality(30, 22.5, "1")
draw_cardinality(71, 46, "1..*")


# ==============================================================================
# ATTRIBUTES (OVALS) & LEAF CONNECTIONS
# ==============================================================================

# --- DEPARTMENT ATTRIBUTES ---
dept_attrs = [
    ((8, 93), "dept_id", True),
    ((20, 96), "dept_name", False),
    ((32, 93), "created_at", False),
]
for pos, name, is_pk in dept_attrs:
    connect(dept_pos, pos)
    draw_attribute(pos[0], pos[1], 8.5, 4, name, is_pk=is_pk)

# --- STUDENT ATTRIBUTES ---
stud_attrs = [
    ((6, 62), "student_id", True),
    ((5, 54), "name", False),
    ((6, 46), "email", False),
    ((15, 44), "phone", False),
    ((25, 44), "semester", False),
    ((32, 46), "password", False),
]
for pos, name, is_pk in stud_attrs:
    connect(stud_pos, pos)
    draw_attribute(pos[0], pos[1], 8.5, 3.8, name, is_pk=is_pk)

# --- REGISTRATION ATTRIBUTES ---
reg_attrs = [
    ((42, 67), "reg_id", True),
    ((50, 68), "reg_date", False),
    ((58, 67), "status", False),
    ((50, 43), "pass_token", False),
]
for pos, name, is_pk in reg_attrs:
    connect(reg_pos, pos)
    draw_attribute(pos[0], pos[1], 8.5, 3.8, name, is_pk=is_pk)

# --- VENUE ATTRIBUTES ---
venue_attrs = [
    ((68, 93), "venue_id", True),
    ((80, 96), "venue_name", False),
    ((92, 94), "location", False),
    ((94, 85), "capacity", False),
]
for pos, name, is_pk in venue_attrs:
    connect(venue_pos, pos)
    draw_attribute(pos[0], pos[1], 8.5, 3.8, name, is_pk=is_pk)

# --- EVENT ATTRIBUTES ---
event_attrs = [
    ((94, 65), "event_id", True),
    ((96, 58), "event_name", False),
    ((95, 50), "event_date", False),
    ((94, 42), "event_time", False),
    ((66, 46), "max_cap", False),
    ((66, 63), "status", False),
]
for pos, name, is_pk in event_attrs:
    connect(event_pos, pos)
    draw_attribute(pos[0], pos[1], 8.5, 3.8, name, is_pk=is_pk)

# --- CATEGORY ATTRIBUTES ---
cat_attrs = [
    ((68, 12), "category_id", True),
    ((80, 9), "category_name", False),
    ((92, 12), "created_at", False),
]
for pos, name, is_pk in cat_attrs:
    connect(cat_pos, pos)
    draw_attribute(pos[0], pos[1], 9.5, 3.8, name, is_pk=is_pk)

# --- ADMIN ATTRIBUTES ---
admin_attrs = [
    ((8, 27), "admin_id", True),
    ((6, 18), "username", False),
    ((10, 10), "password", False),
    ((24, 10), "created_at", False),
]
for pos, name, is_pk in admin_attrs:
    connect(admin_pos, pos)
    draw_attribute(pos[0], pos[1], 8.5, 3.8, name, is_pk=is_pk)

# Header Title Block
ax.text(50, 97, "CEMS — Entity-Relationship (ER) Diagram",
        fontsize=17, fontweight="bold", color=c_text_dark, ha="center", va="center", family="sans-serif")
ax.text(50, 94.2, "Chen Notation (Peter Chen Model) \u2022 Entities (Rectangles), Relationships (Diamonds), Attributes (Ovals)",
        fontsize=10.5, color="#64748b", ha="center", va="center", family="sans-serif")

# Legend
leg_x, leg_y = 50, 4
patches_legend = [
    patches.Rectangle((31, 2.5), 5.5, 2.6, facecolor=c_entity, edgecolor="#1e3a8a", lw=1),
    patches.Polygon([[44, 4.5], [46.5, 3.75], [44, 3], [41.5, 3.75]], facecolor=c_rel, edgecolor="#7f1d1d", lw=1),
    patches.Ellipse((54, 3.75), 5, 2.3, facecolor=c_attr_key, edgecolor=c_attr_key_border, lw=1.2),
    patches.Ellipse((66, 3.75), 5, 2.3, facecolor=c_attr, edgecolor=c_attr_border, lw=1)
]
for p in patches_legend:
    ax.add_patch(p)

ax.text(33.75, 3.75, "Entity", color="white", fontsize=8, fontweight="bold", ha="center", va="center")
ax.text(48, 3.75, "Relationship", color=c_text_dark, fontsize=8.5, ha="left", va="center")
ax.text(58, 3.75, "Primary Key", color=c_text_dark, fontsize=8.5, ha="left", va="center")
ax.text(70, 3.75, "Attribute", color=c_text_dark, fontsize=8.5, ha="left", va="center")

# Save diagram
out_dir = os.path.join(os.getcwd(), "docs")
out_png = os.path.join(out_dir, "er_diagram_chen.png")
out_svg = os.path.join(out_dir, "er_diagram_chen.svg")

plt.tight_layout()
plt.savefig(out_png, dpi=300, bbox_inches="tight", facecolor="white")
plt.savefig(out_svg, bbox_inches="tight", facecolor="white")
plt.close()

print(f"Generated Chen ER Diagram:\n  {out_png}\n  {out_svg}")