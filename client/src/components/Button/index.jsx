const Button = ({ text, onClickListener, className }) => {
    return (
      <button onClick={onClickListener} className={className}>
        {text}
      </button>
    );
  };
  
  export default Button;
  