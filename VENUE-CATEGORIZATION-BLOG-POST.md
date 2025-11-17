# How We Automatically Categorize Squash Venues: A Behind-the-Scenes Look

As squash fans, we all know that squash courts can be found in all sorts of places—from dedicated squash clubs to hotel fitness centers, from university sports facilities to military bases. But how do we know which category each venue belongs to? With over 4,500 venues in our database, manually categorizing each one would be impossible. That's why we've built an intelligent, automated categorization system that uses cutting-edge technology to get it right.

## Our 16 Venue Categories

Before we dive into *how* we categorize, let's look at *what* we're categorizing into. Our system recognizes 16 different venue types:

1. **Dedicated facility** - Venues that are exclusively for squash (like "Squash Club" or "Squash Centre")
2. **Leisure centre** - Multi-sport recreation facilities (sports complexes, recreation centers)
3. **School** - Primary, secondary, and other educational institutions
4. **Gym or health & fitness centre** - Fitness centers and health clubs
5. **Hotel or resort** - Hotels and resorts with squash facilities
6. **College or university** - Higher education institutions
7. **Military** - Military bases and facilities
8. **Shopping centre** - Shopping malls with squash courts
9. **Community hall** - Community centers and civic facilities
10. **Private residence** - Private homes with courts
11. **Business complex** - Office buildings and corporate facilities with sports amenities
12. **Private club** - Sports-focused private membership clubs
13. **Country club** - Golf clubs and country clubs (more social, less sports-focused)
14. **Industrial** - Factories, warehouses, and industrial sites with staff facilities
15. **Other** - Venues that don't fit any of the above (we try to avoid this!)
16. **Don't know** - Venues we haven't categorized yet (this is what we're working on!)

## How We Categorize: A Multi-Layered Approach

Our categorization system uses a sophisticated, multi-step process that gets smarter at each stage. Think of it like a detective solving a case—we start with the most obvious clues and work our way to more complex analysis.

### Step 1: The Name Says It All (Highest Priority)

The first thing we check is the venue's name. After all, if a venue is called "Squash Club" or "Leisure Centre," that's a pretty strong hint! 

Our system analyzes venue names in multiple languages—not just English. We recognize patterns like:
- **"Squash Club"** → Dedicated facility
- **"Leisure Centre"** or **"Recreation Center"** → Leisure centre
- **"Hotel"** or **"Resort"** → Hotel or resort
- **"School"** or **"École"** → School
- And many more in English, Spanish, French, German, and other languages

**Special Case: Dedicated Facility vs. Leisure Centre**

Here's where it gets interesting. If a venue name contains "squash" but *also* mentions other sports (like "tennis," "swimming," or "multi-sport"), we know it's not a dedicated squash facility—it's a multi-sport leisure centre. This is crucial because "Dedicated facility" means **squash-only**, not just "has squash courts."

### Step 2: Combination Logic (The Smart Detective)

Sometimes, a single Google Places type isn't enough. That's where our combination logic comes in. We look at *multiple* types together to make smarter decisions:

- **Gym + Swimming Pool** = Leisure centre (not just a gym)
- **Gym + Multiple Sports Facilities** = Leisure centre
- **Golf Club + Restaurant** = Country club (more social)
- **Private Club (without golf)** = Private club (sports-focused)
- **Office Building + Sports Facilities** = Business complex
- **Factory + Sports Facilities** = Industrial

This helps us distinguish between similar categories. For example, a "Private club" is more sports-focused, while a "Country club" has a more social, colonial-era feel where sports aren't the dominant feature.

### Step 3: Google Places Type Mapping

Google Places API provides detailed information about each venue, including what "type" of place it is. We've built a comprehensive mapping table that translates Google's types into our categories:

- `gym`, `fitness_center`, `health_club` → **Gym or health & fitness centre**
- `sports_complex`, `sports_club`, `recreation_center` → **Leisure centre**
- `hotel`, `resort_hotel` → **Hotel or resort**
- `school`, `primary_school`, `secondary_school` → **School**
- `university`, `college` → **College or university**
- `community_center` → **Community hall**
- `private_club` → **Private club**
- `country_club`, `golf_club` → **Country club**
- `military_base` → **Military**
- `shopping_mall` → **Shopping centre**
- `office_building` → **Business complex**
- And many more...

We check the "primary type" first (highest confidence), then fall back to secondary types if needed (with slightly lower confidence).

### Step 4: Language Translation (Breaking Down Barriers)

What happens when we encounter a venue name in Chinese, Arabic, or Indonesian? Our system automatically detects non-English names and translates them to English using Google Translate API. Then we re-analyze the translated name using the same name-based patterns.

For example:
- **"网球俱乐部"** (Chinese) → Translated to "Tennis Club" → Analyzed
- **"نادي الرياضة"** (Arabic) → Translated to "Sports Club" → Analyzed

This ensures we can categorize venues from around the world, regardless of language.

### Step 5: AI-Powered Fallback (The Expert Consultant)

When all else fails—when the name doesn't give us a clear signal, the Google Places types are ambiguous, or we just need a second opinion—we call in our AI expert: GPT-4.

The AI receives:
- The venue's name and address
- All Google Places data (types, description, etc.)
- Our complete list of categories with definitions

It then makes an intelligent recommendation, often with detailed reasoning. The AI is especially helpful for:
- Distinguishing between similar categories (like "Private club" vs. "Country club")
- Handling edge cases that don't fit standard patterns
- Suggesting when a new category might be needed

**Important Note:** The AI is explicitly trained to understand that "Dedicated facility" means squash-only. If it sees a general sports complex, it will recommend "Leisure centre" instead.

## Confidence Levels: How Sure Are We?

Each categorization comes with a confidence level:

- **HIGH** - We're very confident (e.g., venue name is "Squash Club" or Google Places type is `hotel`)
- **MEDIUM** - We're reasonably confident (e.g., venue name contains "squash" but might be multi-sport)
- **LOW** - We're less certain (e.g., only matched a secondary Google Places type)

We only automatically update the database when confidence is MEDIUM or HIGH, giving us a chance to review LOW confidence cases manually.

## Special Handling: Place ID Management

Google Place IDs can sometimes expire or become invalid. Our system automatically handles this by:

1. **First**, trying Google's free Place ID refresh method
2. **If that fails**, searching for the venue by name and address using Google's Text Search API
3. **If found**, automatically updating the venue's Place ID and retrying categorization
4. **If not found**, flagging the venue for deletion (suggesting it may be permanently closed)

This ensures we're always working with current, valid data.

## The Human Touch

While our system is highly automated, we maintain full transparency and control:

- **Dry-run mode** - Preview all recommendations before making any changes
- **Detailed reports** - See exactly why each venue was categorized the way it was
- **Audit logging** - Every category change is logged with reasoning and confidence
- **Batch processing** - Process venues in small batches (5-50 per day) for careful monitoring
- **Export options** - Download reports as CSV or JSON for review

## Why This Matters

Accurate categorization helps us:
- **Better understand the squash landscape** - Where are courts located? What types of venues host squash?
- **Improve search and filtering** - Find venues by category (e.g., "Show me all hotels with squash courts")
- **Generate insights** - Analyze trends (e.g., "Are more courts in leisure centres or dedicated facilities?")
- **Provide better user experience** - Help players find the right type of venue for their needs

## Continuous Improvement

Our categorization system is constantly learning. We track:
- **Unmapped Google Places types** - New types we haven't seen before
- **AI suggestions** - Potential new categories that might be needed
- **Edge cases** - Venues that don't fit neatly into existing categories

This data helps us refine our mappings and potentially add new categories in the future.

## The Bottom Line

Categorizing 4,500+ venues manually would take years. Our automated system processes them intelligently, using multiple data sources and AI to get it right. But we never forget that behind every venue is a real place where people play squash—and getting the category right helps connect players with the courts they're looking for.

---

*Want to see the system in action? Check out our [venue categorization dashboard](https://spa.test/) to see how venues are distributed across categories, or explore individual venues to see their categorization details.*


