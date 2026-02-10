Import("env")

# Force C++17
env.Append(
    CXXFLAGS=["-std=gnu++17"]
)

# Remove C++11 if present
try:
    env['CXXFLAGS'].remove("-std=gnu++11")
except (ValueError, KeyError):
    pass

print("C++ standard set to gnu++17")
