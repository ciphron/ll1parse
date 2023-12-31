# Overivew
ll1parse is a simple library to simplify creating an LL1 parser for languages generated by a context-free grammar. At present, there is only an implementation in PHP. See the php/languages directory for examples of two simple languages whose grammar is specified in a .cfg file.

# CFG Grammar Files
A .cfg file contains lines consisting of production statements of the form:

X -> \#TOKEN1 <Y>

where X, Y are non-terminals and TOKEN1 is a terminal (a token type). Each line of the file is a distinct production, and EPSILON is a reserved "token" signifying the empty string. The "AB" language is a trivial toy language that consists of strings that repete the base string "AB" one or more times e.g: "ABAB" and "ABABABABAB". Note a string such as "AAB" is not a member of the language.
Its .cfg file contains the 3 lines:

S -> \#A \<T\>

S -> \#EPSILON

T -> \#B \<S\>


# Processor Implementation
To implement a language, one can derive the Processor class and override the functions mkstate, output, step and scan. The function 'mkstate' instantiates an object representing parser state. The function 'output' returns the generated result from a given state argument. The function 'step' updates the parser state when consuming a given token. Finally, the function 'scan' takes a string and tokenizes it, outputting an array of tokens.

This README is far from complete. More documentation to follow.
